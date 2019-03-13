<?php

namespace SchemaCrawler\Jobs\Json;

use SchemaCrawler\Containers\RawData;
use SchemaCrawler\Exceptions\InvalidSchema;
use SchemaCrawler\Jobs\DetailCrawler;
use SchemaCrawler\Sources\JsonSource;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Symfony\Component\DomCrawler\Crawler;

class JsonDetailCrawler extends DetailCrawler implements ShouldQueue
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
     * The object data.
     *
     * @var Crawler
     */
    protected $data = [];


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
     * @param JsonSource $source
     * @param array      $data
     * @internal param array $pathSelectors
     */
    public function __construct(string $url, array $overwriteAttributes, JsonSource $source, array $data)
    {
        parent::__construct($overwriteAttributes, $source);
        $this->pathSelectors = $source->getPathSelectors();
        $this->data = $data;
        $this->url = $url;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws InvalidSchema
     */
    public function handle()
    {
        $this->rawData = $this->getDataFromArray($this->data);
		parent::handle();

    }

    private function getDataFromArray(array $data)
    {
		
        $rawData = new RawData($this->url, $this->source->getId());

        foreach ($this->pathSelectors as $attribute => $pathSelector) {
            if ($attribute !== 'url') {

                $rawData->{$attribute} = $this->source->{camel_case('get_' . $attribute)}($data);
            }

        }

        return $rawData;
    }
    
    public function getUrl()
    {
		return $this->url;
		
	}
}
