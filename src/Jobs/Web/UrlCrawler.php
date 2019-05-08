<?php

namespace SchemaCrawler\Jobs\Web;

use SchemaCrawler\Helper\Helper;
use SchemaCrawler\Jobs\OverviewCrawler;
use SchemaCrawler\Sources\WebSource;
use SchemaCrawler\Browser\Browse;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Symfony\Component\DomCrawler\Crawler;

class UrlCrawler extends OverviewCrawler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The CSS selectors for the paging.
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
     * Create a new job instance.
     *
     * @param WebSource $source
     */
    public function __construct(WebSource $source, $sectionIndex = null)
    {
        parent::__construct($source,$sectionIndex);
        $this->cssSelectors = $source->getCssSelectors()['overview'];
        $this->crawlerSettings = $source->getCrawlerSettings();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $urls = $this->source->getCustomSchemaUrls();
        if(empty($urls)) {
            if ($this->source->detailsFromOverview()) {
                if (!method_exists($this->source, 'getUrlFromNode')) {
                    throw new \Exception('getUrlFromNode method must be defined.');
                }
            }
            $sources = $this->source->getSourceUrls();
            if (!is_null($this->sectionIndex))
                $sources = [$sources[$this->sectionIndex]];

            $urls = $this->getUrlsFromSources($sources);
        }
        $urls = Helper::mergeDuplicateUrls($urls);

        $this->fireUrlEvent($urls);

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
            dispatch(new WebDetailCrawler($detailPage['url'], $detailPage['overwriteAttributes'],
                $this->source, $detailPage['details']));
        }
    }

    protected function getUrlsFromSources(array $sourceUrls)
    {
        $urls = [];
        $currentWebsite = null;

        foreach ($sourceUrls as $source) {

            $currentWebsite = $this->browseToWebsite($source['url']);

            $urls = array_merge($urls, $this->getUrlsFromWebsite($currentWebsite, $source['overwriteAttributes']));

            if (!$this->pagingEnabled()) {
                continue;
            }

            while ($this->getPagingElementOfWebsite($currentWebsite)->count()) {
                $nextUrl = $this->getPagingElementOfWebsite($currentWebsite)->first()->attr('href');
                $currentWebsite = $this->browseToWebsite(Helper::generateAbsoluteUrl($nextUrl, $source['url']));
                $urls = array_merge($urls, $this->getUrlsFromWebsite($currentWebsite, $source['overwriteAttributes']));
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
        return Browse::browse($url, $this->crawlerSettings );
    }

    private function getUrlsFromWebsite(Crawler $website, $overwriteAttributes)
    {
        $urls = [];
        $absoluteUrl = $this->source->getSourceUrls()[0]['url'];

        $website->filter($this->cssSelectors['detailPageLink'])->each(function (Crawler $link) use ($overwriteAttributes, $absoluteUrl, &$urls) {
            $overwriteAttributes = $overwriteAttributes ?: [];
            if($this->source->detailsFromOverview()){
                $url = $this->source->getUrlFromNode($link);
                $details = $link->html();
            }else{
                $url = Helper::generateAbsoluteUrl($link->attr('href'), $absoluteUrl);
                $details = '';
            }
            $urls[] = compact('url', 'overwriteAttributes', 'details');
        });

        return $urls;
    }
}
