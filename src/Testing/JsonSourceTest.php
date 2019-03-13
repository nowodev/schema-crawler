<?php

namespace SchemaCrawler\Testing;

use Illuminate\Foundation\Testing\TestCase;
use Prewk\XmlStringStreamer;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Zttp\Zttp;

abstract class JsonSourceTest extends TestCase
{
    /**
     * Class name of the crawler source
     *
     * @var string
     */
    protected $sourceClass;

    /**
     * @var \SchemaCrawler\Sources\FeedSource
     */
    protected $jsonsource;

    protected $pathSelectors;

    protected $allowedAttributes;

    protected $jsonUrls = [];

    protected function setUp()
    {
        parent::setUp();

        $jsonsourceClass = $this->sourceClass ?: config('schema-crawler.generator.jsonsource.namespace') . '\\' . str_replace('Test', '', (new \ReflectionClass($this))->getShortName());
        $this->jsonsource = new $jsonsourceClass(null);
        $this->jsonUrls = $this->jsonsource->getJsonUrls();
        $this->pathSelectors = $this->jsonsource->getPathSelectors();
        $this->allowedAttributes = config('schema-crawler.attributes_to_crawl');

    }

    /** @test */
    public function it_can_get_the_json()
    {
        foreach ($this->jsonUrls as $jsonUrl) {            

            return $this->assertGreaterThan(0, count($this->getHits($jsonUrl['url'], $jsonUrl['hitsKey'])));

        }
    }

    /** @test */
    public function it_can_get_the_attributes()
    {
        $data = head($this->getHits($this->jsonUrls[0]['url'], $this->jsonUrls[0]['hitsKey']));
        $this->allowedAttributes['url'] = 'required';
        $detailPageUrl = $this->jsonsource->getUrl($data);

        foreach ($this->allowedAttributes as $attribute => $validation) {
            if (str_contains($validation, 'required')) {
                $this->assertNotEmpty(
                    $this->jsonsource->{'get' . ucfirst(camel_case($attribute))}($data),
                    "Couldn't find any {$attribute} element.\n[Tested URL: {$detailPageUrl}]");
            }
        }
    }

    protected function getHits($url, $hitsKey)
    {

        if (method_exists($this->jsonsource, 'getJson')) {
            return $this->jsonsource->getJson($url, $hitsKey);        }

        
        return data_get(Zttp::get($url)->json(), $hitsKey);

    }
}
