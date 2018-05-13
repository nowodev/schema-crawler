<?php

namespace SchemaCrawler\Jobs\Web;

use Prewk\XmlStringStreamer;
use SchemaCrawler\Helper\Helper;
use SchemaCrawler\Sources\FeedSource;
use SchemaCrawler\Sources\WebSource;
use ChromeHeadless\ChromeHeadless;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class FeedCrawler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The crawler source instance.
     *
     * @var FeedSource
     */
    public $source;

    /**
     * The path selectors for the attributes.
     *
     * @var array
     */
    protected $pathSelectors = [];

    /**
     * The urls that have been processed.
     *
     * @var array
     */
    protected $urls = [];

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 2;

    /**
     * Create a new job instance.
     *
     * @param FeedSource $source
     */
    public function __construct(FeedSource $source)
    {
        $this->source = $source;
        $this->pathSelectors = $source->getPathSelectors();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->source->getFeedUrls() as $feed) {

            $filePath = $this->download($feed['url'], array_get($feed, 'zipped', false));

            if (empty($filePath)) {
                continue;
            }

            $stream = $this->getXmlStream($filePath);

            while ($node = trim($stream->getNode())) {
                if (starts_with($node, ('<' . $feed['schemaNode']))) {
                    $this->runDetailCrawler($node, $feed['overwriteAttributes']);
                }
            }

            unlink($filePath);
        }

        $sourceModel = $this->source->getSourceModelClass();
        $sourceModel::findOrFail($this->source->getId())->urlsCrawledEvent($this->urls);
    }

    private function download(string $url, bool $extract = false)
    {
        $filePath = storage_path('schema-crawler/temp/') . md5(time()) . '.xml';

        try {
            file_put_contents($filePath . ($extract ? '.gz' : ''), file_get_contents($url));
        } catch (\Exception $e) {
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

    private function runDetailCrawler($node, $overwriteAttributes = [])
    {
        $nodeCrawler = new Crawler(str_replace(['<![CDATA[', ']]>'], '', $node));
        $url = $this->source->getUrl($nodeCrawler);

        $this->urls[] = $url;

        dispatch(new FeedDetailCrawler($url, $overwriteAttributes, $this->source, $nodeCrawler));
    }

    private function getXmlStream(string $filePath)
    {
        return XmlStringStreamer::createStringWalkerParser($filePath, [
            'expectGT' => true
        ]);
    }
}
