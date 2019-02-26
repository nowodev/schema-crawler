<?php

namespace SchemaCrawler\Jobs\Web;

use ChromeHeadless\Laravel\ChromeHeadless;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use SchemaCrawler\Containers\RawData;
use SchemaCrawler\Exceptions\InvalidSchema;
use SchemaCrawler\Helper\Helper;
use SchemaCrawler\Jobs\DetailCrawler;
use SchemaCrawler\Sources\WebSource;
use Symfony\Component\DomCrawler\Crawler;

class WebDetailCrawler extends DetailCrawler implements ShouldQueue
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
    protected $cssSelectors = [];

    /**
     * The shop specific crawler settings.
     *
     * @var array
     */
    protected $crawlerSettings = [];

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
     * @param string    $url
     * @param array     $overwriteAttributes
     * @param WebSource $source
     * @internal param array $cssSelectors
     */
    public function __construct(string $url, array $overwriteAttributes, WebSource $source)
    {
        parent::__construct($overwriteAttributes, $source);
        $this->url = $url;
        $this->cssSelectors = $source->getCssSelectors()['detail'];
        $this->crawlerSettings = $source->getCrawlerSettings();
    }

    /**
     * Get the url.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws InvalidSchema
     */
    public function handle()
    {
        $website = $this->browseToWebsite($this->url);

        $this->rawData = $this->getDataFromWebsite($website);

        parent::handle();
    }

    private function browseToWebsite($url)
    {
        if ($this->crawlerSettings['type'] === 'scraperapi') {
            return Helper::scraperapiCrawl($url, $this->crawlerSettings['scraperapi_render_js']);
        }

        return ChromeHeadless::url($url)
            ->setBlacklist($this->crawlerSettings['blacklist'])
            ->setExcluded($this->crawlerSettings['excluded'])
            ->getDOMCrawler();
    }

    private function getDataFromWebsite(Crawler $website)
    {
        $data = new RawData($this->url, $this->source->getId());

        foreach ($this->cssSelectors as $attribute => $cssSelector) {
            $data->{$attribute} = $this->source->{camel_case('get_' . $attribute)}($website);
        }

        return $data;
    }
}
