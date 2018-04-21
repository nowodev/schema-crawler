<?php


namespace SchemaCrawler;


use Illuminate\Database\Eloquent\Collection;
use SchemaCrawler\Jobs\UrlCrawler;

class SchemaCrawler
{
    /**
     * A collection of all sources that should be crawled.
     *
     * @var Collection
     */
    protected $sources;

    /**
     * SchemaCrawler constructor.
     */
    public function __construct()
    {
        $sourceClass = config('schema-crawler.source_model');
        $this->sources = $sourceClass::shouldBeCrawled()->get();
    }

    /**
     * Run the crawler.
     *
     * @return mixed
     */
    public static function run()
    {
        return (new static)->dispatchCrawlers();
    }

    /**
     * Dispatch a crawler for each source.
     */
    protected function dispatchCrawlers()
    {
        foreach ($this->sources as $source) {
            $sourceCrawler = $source->getCrawlerClassName();
            dispatch(new UrlCrawler(new $sourceCrawler($source->id)));
        }
    }
}