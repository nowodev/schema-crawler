<?php

namespace SchemaCrawler\Jobs\Web;

use SchemaCrawler\Helper\Helper;
use SchemaCrawler\Models\Source;
use SchemaCrawler\Sources\WebSource;
use ChromeHeadless\ChromeHeadless;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Symfony\Component\DomCrawler\Crawler;

abstract class OverviewCrawler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The crawler source instance.
     *
     * @var WebSource
     */
    public $source;

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
     * @param Source $source
     */
    public function __construct(Source $source)
    {
        $this->source = $source;
    }

    /**
     * Fire a new source model event with the urls that have been crawled.
     *
     * @param array $urls
     */
    protected function fireUrlEvent(array $urls)
    {
        $sourceModel = $this->source->getSourceModelClass();
        event(new UrlsCrawled($urls, $sourceModel::findOrFail($this->source->getId())));
    }
}
