<?php

namespace SchemaCrawler\Jobs;

use SchemaCrawler\Containers\RawData;
use SchemaCrawler\Sources\WebSource;
use ChromeHeadless\ChromeHeadless;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class DetailCrawler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $url = null;

    protected $options = [];

    protected $source = null;

    protected $cssSelectors = [];

    protected $websiteDOM = null;

    protected $schemaClass = null;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 2;

    /**
     * Create a new job instance.
     *
     * @param string    $url
     * @param array     $options
     * @param WebSource $source
     * @internal param array $cssSelectors
     */
    public function __construct(string $url, array $options, WebSource $source)
    {
        $this->url = $url;
        $this->options = $options;
        $this->source = $source;
        $this->cssSelectors = $source->getCssSelectors()['detail'];
        $this->schemaClass = $source->getSchemaModelClass();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->browseToWebsite();

        $adapter = $this->createAdapterFromData($this->getDataFromWebsite());

        $data = $this->mergeOptions($adapter->validateAndGetData());

        $schema = $this->findExistingSchema($data);

        if ($schema == null) {
            $this->schemaClass::createFromCrawlerData($data);
        } else {
            $schema->updateFromCrawlerData($data);
        }
    }

    private function browseToWebsite()
    {
        $this->websiteDOM = ChromeHeadless::url($this->url)->getDOMCrawler();
    }

    private function getDataFromWebsite()
    {
        $data = new RawData($this->url, $this->source->getId());

        foreach ($this->cssSelectors as $attribute => $cssSelector) {
            $data->{$attribute} = $this->source->{camel_case('get_' . $attribute)}($this->websiteDOM);
        }

        return $data;
    }

    private function createAdapterFromData($data)
    {
        $adapterClass = $this->source->getAdapterClass();

        return new $adapterClass($data, $this->source->getAdapterOptions(), config('schema-crawler.attributes_to_crawl'));
    }

    private function findExistingSchema($data)
    {
        $query = $this->schemaClass::query();

        foreach ($this->schemaClass::getUniqueKeys() as $key) {
            $query->where($key, $data[$key]);
        }

        return $query->first();
    }

    private function mergeOptions($data)
    {
        foreach ($this->options as $key => $value) {
            $data[$key] = $value;
        }

        return $data;
    }
}
