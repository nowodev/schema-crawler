<?php


namespace SchemaCrawler\Sources;


/**
 * Class Source
 * @package SchemaCrawler\Sources
 */
abstract class Source
{
    /**
     * The database id of the source.
     *
     * @var mixed
     */
    protected $id;

    /**
     * Options defined here will be accessible in the adapter.
     *
     * @var array
     */
    protected $adapterOptions = [];

    /**
     * The default adapter that will be used can be overwritten here.
     *
     * @var string
     */
    protected $adapter;

    /**
     * The class name of the schema model.
     *
     * @var string
     */
    protected $schemaModel;

    /**
     * The class name of the source model.
     *
     * @var string
     */
    protected $sourceModel;

    /**
     * The name and specification of the attributes that should be crawled.
     *
     * @var array
     */
    protected $allowedAttributes = [];


    /**
     * Overview crawler class
     * @var string
     */
    protected $overviewCrawlerClass = '';

    /**
     * Overview jobs to be divived into separate jobs
     * @var bool
     */
    protected $dividedOverviewJobs = false;

    /**
     * WebSource constructor.
     *
     * @param $sourceId The database id of the source.
     */
    public function __construct($sourceId)
    {
        $this->id = $sourceId;
        $this->adapter = config('schema-crawler.default_adapter');
        $this->schemaModel = config('schema-crawler.schema_model');
        $this->sourceModel = config('schema-crawler.source_model');
        $this->allowedAttributes = array_keys(config('schema-crawler.attributes_to_crawl'));
    }

    /**
     * Get the database id of the source.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the class name of the schema model.
     *
     * @return string
     */
    public function getSchemaModelClass(): string
    {
        return $this->schemaModel;
    }

    /**
     * Get the class name of the source model.
     *
     * @return string
     */
    public function getSourceModelClass(): string
    {
        return $this->sourceModel;
    }

    /**
     * Get the adapter options.
     *
     * @return array
     */
    public function getAdapterOptions(): array
    {
        return $this->adapterOptions;
    }

    /**
     * Get the adapter class name.
     *
     * @return string
     */
    public function getAdapterClass()
    {
        return $this->adapter;
    }

    /**
     * @return bool
     */
    protected function dividedOverviewJobs()
    {
        return (bool) $this->dividedOverviewJobs;
    }

    /**
     * Start the crawling process.
     *
     * @return mixed
     */
    public function run(){

        if($this->dividedOverviewJobs())
        {
            foreach ($this->getSections() as $index=>$section) {
                dispatch(new $this->overviewCrawlerClass($this, $index));
           }
        }else
            dispatch(new $this->overviewCrawlerClass($this));
    }

    /**
     * Get the urls array e.g. $sourceUrls
     * @return array
     */
    abstract protected function getSections() : array;
}