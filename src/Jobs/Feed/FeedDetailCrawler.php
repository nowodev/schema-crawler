<?php

namespace SchemaCrawler\Jobs\Web;

use SchemaCrawler\Containers\RawData;
use SchemaCrawler\Exceptions\CrawlerException;
use SchemaCrawler\Exceptions\InvalidSchema;
use SchemaCrawler\Jobs\DetailCrawler;
use SchemaCrawler\Sources\FeedSource;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Symfony\Component\DomCrawler\Crawler;

class FeedDetailCrawler extends DetailCrawler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The url that should be crawled.
     *
     * @var string
     */
    protected $url = null;

    /**
     * The CSS selectors for the attributes of the schema.
     *
     * @var array
     */
    protected $pathSelectors = [];

    /**
     * The node of the XML.
     *
     * @var Crawler
     */
    protected $node = null;

    /**
     * Values of the collected GroupedAttributes e.g. sizes
     *
     * @var Crawler
     */
    protected $groupedValues = [];

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;


    /**
     * Create a new job instance.
     *
     * @param string     $url
     * @param array      $overwriteAttributes
     * @param FeedSource $source
     * @param            $node
     * @internal param array $cssSelectors
     */
    public function __construct(string $url, array $overwriteAttributes, FeedSource $source, $node, $groupedValues)
    {
        parent::__construct($overwriteAttributes, $source);
        $this->pathSelectors = $source->getPathSelectors();
        $this->node = $node;
        $this->url = $url;
        $this->groupedValues = $groupedValues;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws InvalidSchema
     */
    public function handle()
    {
        try {
            $this->rawData = $this->getDataFromNode(new Crawler(str_replace(['<![CDATA[', ']]>'], '', $this->node)));
        }catch (\Exception $e)
        {
            throw new CrawlerException($e->getMessage(), $e->getCode(), $e);
        }
        parent::handle();

    }

    private function getDataFromNode(Crawler $node)
    {

        $data = new RawData($this->url, $this->source->getId());

        foreach ($this->pathSelectors as $attribute => $pathSelector) {
            if ($attribute !== 'url') {
				if( in_array($attribute, $this->source->getGroupedAttributes()))
				{
					$data->{$attribute} = $this->groupedValues[$attribute] ?? [];
				}else{

					$data->{$attribute} = $this->source->{camel_case('get_' . $attribute)}($node);
				}
            }
        }

        return $data;
    }

    public function getUrl()
    {
		return $this->url;

	}
}
