<?php

namespace SchemaCrawler\Jobs\Json;


use SchemaCrawler\Exceptions\CrawlerException;
use SchemaCrawler\Jobs\OverviewCrawler;
use SchemaCrawler\Sources\JsonSource;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Zttp\Zttp;

class JsonCrawler extends OverviewCrawler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The path selectors for the attributes.
     *
     * @var array
     */
    protected $pathSelectors = [];

    /**
     * Create a new job instance.
     *
     * @param JsonSource $source
     */
    public function __construct(JsonSource $source, $sectionIndex = null)
    {
        parent::__construct($source,$sectionIndex);
        $this->pathSelectors = $source->getPathSelectors();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $sections = $this->source->getJsonUrls();
            if (!is_null($this->sectionIndex)) {
                $sections = [$sections[$this->sectionIndex]];
            }
            foreach ($sections as $jsonUrl) {

                $allData = $this->getJson($jsonUrl['url'], $jsonUrl['hitsKey']);

                foreach ($allData as $data) {
                    $this->runDetailCrawler($data, $jsonUrl['overwriteAttributes']);
                }
            }

            $this->fireUrlEvent($this->urls);
        }catch (\Exception $e)
        {
            throw new CrawlerException($e->getMessage(), $e->getCode(), $e);
        }
    }

    protected function getJson($url, $hitsKey): array
    {
        if( method_exists($this->source, 'getJson'))
        {
            return $this->source->getJson($url, $hitsKey);
        }

        return data_get(Zttp::get($url)->json(), $hitsKey);
    }

    private function runDetailCrawler($data, $overwriteAttributes = [])
    {

        if(!$this->source->shouldBeCrawled($data))
			return;
        $url = trim($this->source->getUrl($data));

        $this->urls[] = compact('url', 'overwriteAttributes');

        // run detail crawler for the previous node
        dispatch(new JsonDetailCrawler($url, $overwriteAttributes,
            $this->source, $data));
    }
}
