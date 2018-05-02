<?php

namespace SchemaCrawler\Jobs;

use SchemaCrawler\Helper\Helper;
use SchemaCrawler\Sources\WebSource;
use ChromeHeadless\ChromeHeadless;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Symfony\Component\DomCrawler\Crawler;

class UrlCrawler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The crawler source instance.
     *
     * @var WebSource
     */
    public $source;

    /**
     * The CSS selectors for the paging.
     *
     * @var array
     */
    protected $cssSelectors = [];

    /**
     * The urls that have been crawled from the paging overview.
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
     * @param WebSource $source
     */
    public function __construct(WebSource $source)
    {
        $this->source = $source;
        $this->cssSelectors = $source->getCssSelectors()['overview'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $urls = $this->getUrlsFromSources($this->source->getSourceUrls());

        $urls = Helper::mergeDuplicateUrls($urls);

        $sourceModel = $this->source->getSourceModelClass();

        $sourceModel::findOrFail($this->source->getId())->urlsCrawledEvent($urls);

        $this->runDetailCrawlers($urls);
    }

    /**
     * Run the detail crawler for each url.
     *
     * @param array $urls
     */
    public function runDetailCrawlers(array $urls)
    {
        foreach ($urls as $detailPage) {
            dispatch(new DetailCrawler($detailPage['url'], $detailPage['options'], $this->source));
        }
    }

    protected function getUrlsFromSources(array $sourceUrls)
    {
        $urls = [];
        $currentWebsite = null;

        foreach ($sourceUrls as $source) {

            $currentWebsite = $this->browseToWebsite($source['url']);

            $urls = array_merge($urls, $this->getUrlsFromWebsite($currentWebsite, $source['options']));

            if (!$this->pagingEnabled()) {
                continue;
            }

            while ($this->getPagingElementOfWebsite($currentWebsite)->count()) {
                $nextUrl = $this->getPagingElementOfWebsite($currentWebsite)->first()->attr('href');
                $currentWebsite = $this->browseToWebsite(Helper::generateAbsoluteUrl($nextUrl, $source['url']));
                $urls = array_merge($urls, $this->getUrlsFromWebsite($currentWebsite, $source['options']));
            }
        }

        return $urls;
    }

    private function getPagingElementOfWebsite(Crawler $website)
    {
        return $website->filter($this->cssSelectors['nextPageLink']);
    }

    private function pagingEnabled()
    {
        return array_has($this->cssSelectors, 'nextPageLink') AND !empty($this->cssSelectors['nextPageLink']);
    }

    private function browseToWebsite(string $url)
    {
        return ChromeHeadless::url($url)->getDOMCrawler();
    }

    private function getUrlsFromWebsite(Crawler $website, $options)
    {
        $urls = [];
        $absoluteUrl = $this->source->getSourceUrls()[0]['url'];

        $website->filter($this->cssSelectors['detailPageLink'])->each(function (Crawler $link) use ($options, $absoluteUrl, &$urls) {
            $url = Helper::generateAbsoluteUrl($link->attr('href'), $absoluteUrl);
            $options = $options ?: [];
            $urls[] = compact('url', 'options');
        });

        return $urls;
    }
}
