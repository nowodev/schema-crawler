<?php

namespace SchemaCrawler\Jobs;

use SchemaCrawler\Events\UrlsCrawled;
use SchemaCrawler\Sources\Source;
use SchemaCrawler\Sources\WebSource;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

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
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 1200;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 2;

    /**
     * @var null|int
     */
    protected $sectionIndex = null;

    /**
     * Create a new job instance.
     * @param Source $source
     */
    public function __construct(Source $source, $sectionIndex = null)
    {
        $this->source = $source;
        $this->sectionIndex = $sectionIndex;
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
    
     /**
     * Get the tags that should be assigned to the job.
     *
     * @return array
     */
    public function tags()
    {
        return ['overview','crawler', $this->source->getId()];
    }
}
