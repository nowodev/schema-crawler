<?php

namespace SchemaCrawler\Jobs\Web;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use SchemaCrawler\Containers\RawData;
use SchemaCrawler\Exceptions\InvalidSchema;
use SchemaCrawler\Helper\Helper;
use SchemaCrawler\Jobs\DetailCrawler;
use SchemaCrawler\Sources\FeedSource;
use SchemaCrawler\Sources\WebSource;
use ChromeHeadless\ChromeHeadless;
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
     * The DOM of the crawled website.
     *
     * @var Crawler
     */
    protected $websiteDOM = null;

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
     * @param Crawler    $node
     * @internal param array $cssSelectors
     */
    public function __construct(string $url, array $overwriteAttributes, FeedSource $source, Crawler $node)
    {
        parent::__construct($overwriteAttributes, $source);
        $this->pathSelectors = $source->getPathSelectors();
        $this->url = $url;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws InvalidSchema
     */
    public function handle()
    {
        $this->rawData = $this->getDataFromNode($this->node);

        parent::handle();
    }

    private function getDataFromNode(Crawler $node)
    {
        $data = new RawData($this->url, $this->source->getId());

        foreach ($this->pathSelectors as $attribute => $pathSelector) {
            $data->{$attribute} = $this->source->{camel_case('get_' . $attribute)}($node);
        }

        return $data;
    }
}
