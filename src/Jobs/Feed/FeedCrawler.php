<?php

namespace SchemaCrawler\Jobs\Web;

use Prewk\XmlStringStreamer;
use SchemaCrawler\Exceptions\CrawlerException;
use SchemaCrawler\Jobs\OverviewCrawler;
use SchemaCrawler\Sources\FeedSource;
use SchemaCrawler\Exceptions\GetFeedContentException;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;


class FeedCrawler extends OverviewCrawler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The path selectors for the attributes.
     *
     * @var array
     */
    protected $pathSelectors = [];

    /**
     * The url of the previous node
     *
     * @var string
     */
    protected $previousUrl = '';

    /**
     * The OverwriteAttributes of the previous node
     *
     * @var string
     */
    protected $previousOverwriteAttributes = [];

    /**
     * Values of the grouped attributes
     *
     * @var array
     */
    protected $groupedValues = [];

    /**
     * Previous node
     *
     * @var string
     */
    protected $previousNode;

    /**
     * Create a new job instance.
     *
     * @param FeedSource $source
     */
    public function __construct(FeedSource $source, $sectionIndex = null)
    {
        parent::__construct($source, $sectionIndex);
        $this->pathSelectors = $source->getPathSelectors();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            foreach ($this->source->getFeedUrls() as $feed) {

                $filePath = $this->download($feed['url'], array_get($feed, 'zipped', false));

                if (empty($filePath)) {
                    continue;
                }

                $stream = $this->getXmlStream($filePath, ($feed['depth'] ?? 2));

                while ($node = trim($stream->getNode())) {

                    if (starts_with($node, ('<' . $feed['schemaNode']))) {

                        $this->runDetailCrawler($node, $feed['overwriteAttributes']);
                    }
                }

                unlink($filePath);
            }

            $this->fireUrlEvent($this->urls);
        }catch (\Exception $e)
        {
            throw new CrawlerException($e->getMessage(), $e->getCode(), $e);
        }
    }

    private function download(string $url, bool $extract = false)
    {
        $filePath = storage_path('schema-crawler/temp/') . md5(time()) . '.xml';

        try {
            file_put_contents($filePath . ($extract ? '.gz' : ''), $this->getFileContent($url));
        } catch ( GetFeedContentException $e ) {
            throw $e;
        }
        catch (\Exception $e ) {
            $filePath = null;
        }

        if (!$extract OR empty($filePath)) {
            return $filePath;
        }

        $zip = new Process(['gzip', '-dk', $filePath . '.gz']);

        $zip->run();

        if (!$zip->isSuccessful()) {
            throw new ProcessFailedException($zip);
        }

        unlink($filePath . '.gz');
        return $filePath;
    }

    protected function getFileContent($url)
    {
        try {
            if (method_exists($this->source, 'getFeedContent')) {
                $content = $this->source->getFeedContent($url);;
                if (!empty($content))
                    return $content;
            }
        }catch (\Exception $e){
            throw new GetFeedContentException($e->getMessage(),0, $e );
        }

        return file_get_contents($url);
    }


    private function runDetailCrawler($node, $overwriteAttributes = [])
    {

        $nodeCrawler = new Crawler(str_replace(['<![CDATA[', ']]>'], '', $node));
        if(!$this->source->shouldBeCrawled($nodeCrawler))
			return;
        $url = trim($this->source->getUrl($nodeCrawler));

        if( !is_null($this->previousNode) && $this->previousUrl !==  $url )
        {
			$this->urls[] = compact('url', 'overwriteAttributes');

			// run detail crawler for the previous node
			dispatch(new FeedDetailCrawler($this->previousUrl, $this->previousOverwriteAttributes,
				$this->source, $this->previousNode, ($this->groupedValues[$this->previousUrl] ?? [])));
		}

		$this->previousOverwriteAttributes = $overwriteAttributes;
		$this->previousNode = $node;
		$this->previousUrl = $url;
		$this->collectGroupedValues($url, $nodeCrawler);
    }

    private function getXmlStream(string $filePath, $depth = 2)
    {
        return XmlStringStreamer::createStringWalkerParser($filePath, [
            'expectGT' => true,
            'captureDepth'=> $depth
        ]);
    }

    protected function collectGroupedValues($url, crawler $node)
    {
		if( !empty( $this->source->getGroupedAttributes() ) )
			foreach($this->source->getGroupedAttributes() as $attr)
				$this->groupedValues[$url][$attr][] = $this->source->{'get'.ucfirst($attr)}($node);
	}
}
