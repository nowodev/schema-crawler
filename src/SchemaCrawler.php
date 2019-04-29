<?php


namespace SchemaCrawler;


use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class SchemaCrawler
{
    /**
     * A collection of all sources that should be crawled.
     *
     * @var Collection
     */
    protected $sources;


    /**
     * Class name of the source model.
     *
     * @var string
     */
    protected $sourceClass;

    /**
     * SchemaCrawler constructor.
     */
    public function __construct()
    {
        $this->sourceClass = config('schema-crawler.source_model');
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
     * Run a single crawler.
     *
     * @param $source
     */
    public static function runSource($source)
    {
        return (new static)->dispatchCrawler($source);
    }

    /**
     * Dispatch a crawler for each source.
     */
    protected function dispatchCrawlers()
    {
        foreach ($this->sourceClass::shouldBeCrawled()->get() as $source) {
            if($source->hasCrawler()) {
                $sourceCrawler = $source->getCrawlerClassName();
                (new $sourceCrawler($source->id))->run();
            }
        }
    }

    /**
     * Dispatch a crawler of a specific source.
     *
     * @param $source
     */
    protected function dispatchCrawler($source)
    {
        $source = $source instanceof Model ? $source : $this->sourceClass::where((new $this->sourceClass())->getRouteKeyName(), $source)
            ->firstOrFail();
        $sourceCrawler = $source->getCrawlerClassName();
        (new $sourceCrawler($source->id))->run();
    }
}
