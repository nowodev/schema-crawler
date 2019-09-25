<?php

namespace SchemaCrawler\Jobs\Csv;

use SchemaCrawler\Containers\RawData;
use SchemaCrawler\Exceptions\CrawlerException;
use SchemaCrawler\Exceptions\InvalidSchema;
use SchemaCrawler\Jobs\DetailCrawler;
use SchemaCrawler\Sources\CsvSource;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Symfony\Component\DomCrawler\Crawler;

class CsvDetailCrawler extends DetailCrawler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The url that should be crawled.
     *
     * @var string
     */
    protected $url = null;

    /**
     * The CSS selectors for the attributes of the schema.
     *
     * @var array
     */
    protected $pathSelectors = [];

    /**
     * The record of the csv
     *
     * @var array
     */
    protected $record = [];

    /**
     * Values of the collected GroupedAttributes e.g. sizes
     *
     * @var array
     */
    protected $groupedValues = [];

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;


    /**
     * Create a new job instance.
     *
     * @param string     $url
     * @param array      $overwriteAttributes
     * @param CsvSource $source
     * @param array      $record
     * @param array      $groupedValues
     * @internal param array $pathSelectors
     */
    public function __construct(string $url, array $overwriteAttributes,
                                CsvSource $source, array $record, $groupedValues)
    {
        parent::__construct($overwriteAttributes, $source);
        $this->pathSelectors = $source->getPathSelectors();
        $this->record = $record;
        $this->url = $url;
        $this->groupedValues = $groupedValues;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws InvalidSchema
     */
    public function handle()
    {
        try {
            $this->rawData = $this->getDataFromRecord($this->record);
        }catch(\Exception $e)
        {
            throw new CrawlerException($e->getMessage(), $e->getCode(), $e);
        }
        parent::handle();
    }

    private function getDataFromRecord(array $record)
    {

        $data = new RawData($this->url, $this->source->getId());

        foreach ($this->pathSelectors as $attribute => $pathSelector) {
            if ($attribute !== 'url') {
                if( in_array($attribute, $this->source->getGroupedAttributes()))
                {
                    $data->{$attribute} = $this->groupedValues[$attribute] ?? [];
                }else{

                    $data->{$attribute} = $this->source->{camel_case('get_' . $attribute)}($record);
                }
            }
        }

        return $data;
    }

    public function getUrl()
    {
        return $this->url;

    }
}
