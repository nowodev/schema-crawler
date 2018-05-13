<?php

namespace SchemaCrawler\Jobs;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use SchemaCrawler\Containers\RawData;
use SchemaCrawler\Exceptions\InvalidSchema;
use SchemaCrawler\Helper\Helper;
use SchemaCrawler\Sources\Source;
use SchemaCrawler\Sources\WebSource;
use ChromeHeadless\ChromeHeadless;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Symfony\Component\DomCrawler\Crawler;

abstract class DetailCrawler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Attributes that should be overwritten.
     *
     * @var array
     */
    protected $overwriteAttributes = [];

    /**
     * The crawler source instance.
     *
     * @var WebSource
     */
    protected $source = null;

    /**
     * The raw data of the crawled schema.
     *
     * @var RawData
     */
    protected $rawData = null;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * Create a new job instance.
     *
     * @param array  $overwriteAttributes
     * @param Source $source
     * @internal param array $cssSelectors
     */
    public function __construct(array $overwriteAttributes, Source $source)
    {
        $this->overwriteAttributes = $overwriteAttributes;
        $this->source = $source;
    }

    /**
     * Get the overwriteAttributes.
     *
     * @return array
     */
    public function getOverwriteAttributes(): array
    {
        return $this->overwriteAttributes;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws InvalidSchema
     */
    public function handle()
    {
        $this->rawData->validate();

        $adapter = $this->createAdapterFromData($this->rawData);

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

    /**
     * Generates an adapter with the given raw data.
     *
     * @param RawData $data
     * @return mixed
     */
    public function createAdapterFromData(RawData $data)
    {
        $adapterClass = $this->source->getAdapterClass();
        return new $adapterClass($data, $this->source->getAdapterOptions(), config('schema-crawler.attributes_to_crawl'), $this->overwriteAttributes);
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
