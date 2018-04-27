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
     * The DOM of the current website.
     *
     * @var Crawler
     */
    protected $currentWebsite = null;

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
        $this->urls = Helper::mergeDuplicateUrls($this->getUrlsFromSources());
        $sourceModel = $this->source->getSourceModelClass();
        $sourceModel::findOrFail($this->source->getId())->urlsCrawledEvent($this->urls);

        foreach ($this->urls as $detailPage) {
            dispatch(new DetailCrawler($detailPage['url'], $detailPage['options'], $this->source));
        }
    }

    protected function getUrlsFromSources()
    {
        $urls = [];

        foreach ($this->source->getSourceUrls() as $source) {

            $this->browseToWebsite($source['url']);

            $urls[] = $this->getUrlsFromCurrentWebsite($source['options']);

            if (!$this->pagingEnabled()) {
                continue;
            }

            while ($this->getPagingElement()->count()) {
                $nextUrl = $this->getPagingElement()->first()->attr('href');
                $this->browseToWebsite(Helper::generateAbsoluteUrl($nextUrl, $source['url']));
                $this->getUrlsFromCurrentWebsite($source['options']);
            }
        }

        return $urls;
    }

    private function getPagingElement()
    {
        return $this->currentWebsite->filter($this->cssSelectors['nextPageLink']);
    }

    private function pagingEnabled()
    {
        return array_has($this->cssSelectors, 'nextPageLink') AND !empty($this->cssSelectors['nextPageLink']);
    }

    private function browseToWebsite(string $url)
    {
        $this->currentWebsite = ChromeHeadless::url($url)->getDOMCrawler();
    }

    private function getUrlsFromCurrentWebsite($options)
    {
        $urls = [];
        $absoluteUrl = $this->source->getSourceUrls()[0]['url'];

        $this->currentWebsite->filter($this->cssSelectors['detailPageLink'])->each(function (Crawler $link) use ($options, $absoluteUrl) {
            $url = Helper::generateAbsoluteUrl($link->attr('href'), $absoluteUrl);
            $urls[] = compact('url', 'options');
        });

        return $urls;
    }
}
