<?php

namespace SchemaCrawler\Jobs;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use SchemaCrawler\Containers\RawData;
use SchemaCrawler\Exceptions\InvalidSchema;
use SchemaCrawler\Helper\Helper;
use SchemaCrawler\Sources\WebSource;
use ChromeHeadless\ChromeHeadless;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Symfony\Component\DomCrawler\Crawler;

class DetailCrawler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The url that should be crawled.
     *
     * @var string
     */
    protected $url = null;

    /**
     * Attributes that should be overwritten.
     *
     * @var array
     */
    protected $options = [];

    /**
     * The crawler source instance.
     *
     * @var WebSource
     */
    protected $source = null;

    /**
     * The CSS selectors for the attributes of the schema.
     *
     * @var array
     */
    protected $cssSelectors = [];

    /**
     * The DOM of the crawled website.
     *
     * @var Crawler
     */
    protected $websiteDOM = null;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

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
    }

    /**
     * Get the url.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Get the options.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws InvalidSchema
     */
    public function handle()
    {
        $website = $this->browseToWebsite($this->url);

        $rawData = $this->getDataFromWebsite($website);

        $rawData->validate();

        $adapter = $this->createAdapterFromData($rawData);

        $data = $adapter->validateAndGetData();

        $schemaClass = $this->source->getSchemaModelClass();
        $schema = $this->findExistingSchema($schemaClass, $data);

        if ($schema == null) {
            $schemaClass::createFromCrawlerData($data);
        } else {
            $schema->updateFromCrawlerData($data);
        }
    }

    /**
     * The job failed to process.
     *
     * @param \Exception $exception
     * @return void
     */
    public function failed(\Exception $exception)
    {
        if ($exception instanceof InvalidSchema) {
            DB::table('invalid_schemas')->updateOrInsert([
                'url' => $this->url,
            ], [
                'source_id'        => $this->source->getId(),
                'validation_error' => $exception->getFirstValidationError(),
                'raw_data'         => $exception->getRawData() == null ? null : json_encode($exception->getRawData()),
                'extracted_data'   => $exception->getExtractedData() == null ? null : json_encode($exception->getExtractedData()),
                'failed_at'        => Carbon::now()
            ]);
        }
    }

    private function browseToWebsite($url)
    {
        return ChromeHeadless::url($url)->getDOMCrawler();
    }

    private function getDataFromWebsite(Crawler $website)
    {
        $data = new RawData($this->url, $this->source->getId());

        foreach ($this->cssSelectors as $attribute => $cssSelector) {
            $data->{$attribute} = $this->source->{camel_case('get_' . $attribute)}($website);
        }

        return $data;
    }

    /**
     * Generates an adapter with the given raw data.
     *
     * @param RawData $data
     * @return mixed
     */
    public function createAdapterFromData(RawData $data)
    {
        $adapterClass = $this->source->getAdapterClass();
        return new $adapterClass($data, $this->source->getAdapterOptions(), config('schema-crawler.attributes_to_crawl'), $this->options);
    }

    /**
     * Find an existing schema by the given data.
     *
     * @param $schemaClass
     * @param $data
     * @return Model|null
     */
    public function findExistingSchema($schemaClass, $data)
    {
        $query = $schemaClass::query();

        foreach ($schemaClass::getUniqueKeys() as $key) {
            $query->where($key, $data[$key]);
        }

        return $query->first();
    }
}
