<?php

namespace SchemaCrawler\Jobs;

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
    protected $source;

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
        $this->getUrlsFromSources();

        $this->mergeDuplicateUrls();

        $sourceModel = $this->source->getSourceModelClass();
        $sourceModel::findOrFail($this->source->getId())->urlsCrawledEvent($this->urls);

        foreach ($this->urls as $detailPage) {
            dispatch(new DetailCrawler($detailPage['url'], $detailPage['options'], $this->source));
        }
    }

    private function mergeDuplicateUrls()
    {
        $newUrls = [];

        foreach ($this->urls as $el) {
            $key = array_search($el['url'], array_column($newUrls, 'url'));
            if ($key === false) {
                array_push($newUrls, $el);
                continue;
            }
            $newUrls[$key] = array_merge_recursive(array_filter($el), $newUrls[$key]);
            $newUrls[$key]['url'] = implode('', array_unique($newUrls[$key]['url']));
        }

        $this->urls = $newUrls;
    }

    private function getUrlsFromSources()
    {
        foreach ($this->source->getSourceUrls() as $source) {

            $this->browseToWebsite($source['url']);

            $this->getUrlsFromCurrentWebsite($source['options']);

            if (!$this->pagingEnabled()) {
                continue;
            }

            while ($this->getPagingElement()->count()) {
                $nextUrl = $this->getPagingElement()->first()->attr('href');
                $this->browseToWebsite($this->generateAbsoluteUrl($nextUrl, $source['url']));
                $this->getUrlsFromCurrentWebsite();
            }
        }
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

    private function generateAbsoluteUrl($url, $exampleUrl)
    {
        $matches = [];
        preg_match("/^(http(s?):)?\/\/[0-9A-z\.\-]+\//", $url, $matches);

        if (count($matches)) {
            return $url;
        }

        preg_match("/^(http(s?):)?\/\/[0-9A-z\.\-]+\//", $exampleUrl, $matches);

        return $matches[0] . ltrim('/', $url);
    }

    private function getUrlsFromCurrentWebsite($options)
    {
        $this->currentWebsite->filter($this->cssSelectors['detailPageLink'])->each(function (Crawler $link) use ($options) {
            $this->addUrl($link->attr('href'), $options);
        });
    }

    private function addUrl(string $url, array $options = null)
    {
        $url = $this->generateAbsoluteUrl($url);
        array_push($this->urls, compact('url', 'options'));
    }
}
