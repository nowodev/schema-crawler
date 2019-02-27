<?php


namespace SchemaCrawler\Testing;

use SchemaCrawler\Browser\Browse;
use ChromeHeadless\Exceptions\ChromeException;
use Illuminate\Foundation\Testing\TestCase;
use SchemaCrawler\Helper\Helper;

abstract class WebSourceTest extends TestCase
{
    /**
     * Class name of the crawler source
     *
     * @var string
     */
    protected $sourceClass;

    /**
     * @var \SchemaCrawler\Sources\WebSource
     */
    protected $websource;

    protected $cssSelectors;

    protected $crawlerSettings;

    protected $sourceUrls;

    protected $allowedAttributes;

    protected function setUp()
    {
        parent::setUp();

        $websourceClass = $this->sourceClass 
            ?: config('schema-crawler.generator.websource.namespace') . '\\' 
            . str_replace('Test', '', (new \ReflectionClass($this))->getShortName());
        $this->websource = new $websourceClass(null);
        $this->sourceUrls = $this->websource->getSourceUrls();
        $this->cssSelectors = $this->websource->getCssSelectors();
        $this->crawlerSettings = $this->websource->getCrawlerSettings();
        $this->allowedAttributes = config('schema-crawler.attributes_to_crawl');
    }

    /** 
     * @test
     */
    public function it_can_get_schema_urls_from_source_pages()
    {
        if (! empty($this->websource->getCustomSchemaUrls())) {
            return $this->assertGreaterThan(0, count($this->websource->getCustomSchemaUrls()));
        }

        foreach ($this->sourceUrls as $sourcePage) {            
            $sourcePageDOM = $this->runCrawler($sourcePage['url']);

            $this->assertGreaterThan(
                0, 
                $sourcePageDOM->filter($this->cssSelectors['overview']['detailPageLink'])->count(), 
                "Couldn't find any schema urls.\n[Tested URL: {$sourcePage['url']}]"
            );
        }
    }

    /** 
     * @test
     */
    public function it_can_get_the_paging()
    {
        if (empty($this->cssSelectors['overview']['nextPageLink'])) {
            return $this->assertTrue(true); // this source has no paging enabled
        }

        foreach ($this->sourceUrls as $sourcePage) {
            $sourcePageDOM = $this->runCrawler($sourcePage['url']);

            if ($sourcePageDOM->filter($this->cssSelectors['overview']['nextPageLink'])->count() > 0) {
                return $this->assertTrue(true);
            }
        }

        $this->fail("Couldn't find a paging element on any source url.");
    }

    /** 
     * @test
     */
    public function it_can_get_the_required_attributes()
    {
        if (! empty($this->websource->getCustomSchemaUrls())) {
            $detailPageUrl = $this->websource->getCustomSchemaUrls()[0]['url'];
        } else {
            $sourcePageDOM = $this->runCrawler($this->sourceUrls[0]['url']);
            $detailPageUrl = $sourcePageDOM->filter($this->cssSelectors['overview']['detailPageLink'])->attr('href');
        }

        $absoluteUrl = Helper::generateAbsoluteUrl($detailPageUrl, $this->sourceUrls[0]['url']);
        $detailPageDOM = $this->runCrawler($absoluteUrl);

        foreach ($this->allowedAttributes as $attribute => $validation) {
            if (str_contains($validation, 'required')) {
                $this->assertNotEmpty(
                    $this->websource->{'get' . ucfirst(camel_case($attribute))}($detailPageDOM),
                    "Couldn't find any {$attribute} element.\n[Tested URL: {$detailPageUrl}]");
            }
        }
    }

    /** 
     * @test
     */
    public function it_does_not_get_schema_urls_from_invalid_sources()
    {
        if (!empty($this->websource->getCustomSchemaUrls())) {
            return $this->assertTrue(true);
        }

        $invalidSourceUrl = $this->createInvalidUrl($this->sourceUrls[0]['url']);

        try {
            $sourcePageDOM = $this->runCrawler($invalidSourceUrl);
        } catch (Exception $e) {
            return $this->assertContains('HTTP Response', $e->getMessage());
        }

        $this->assertEquals(
            0, 
            $sourcePageDOM->filter($this->cssSelectors['overview']['detailPageLink'])->count(), 
            "Found schema urls on an invalid source. Define a more precise page link css selector to avoid this.
            \n[Tested URL: {$invalidSourceUrl}]"
        );
    }

    /** 
     * @test
     */
    public function it_does_not_get_attributes_from_invalid_pages()
    {
        if (! empty($this->websource->getCustomSchemaUrls())) {
            $detailPageUrl = $this->websource->getCustomSchemaUrls()[0]['url'];
        } else {
            $sourcePageDOM = $this->runCrawler($this->sourceUrls[0]['url']);
            $detailPageUrl = $sourcePageDOM->filter($this->cssSelectors['overview']['detailPageLink'])->attr('href');
        }

        $invalidDetailPageUrl = $this->createInvalidUrl($detailPageUrl);

        try {
            $absoluteUrl = Helper::generateAbsoluteUrl($invalidDetailPageUrl, $this->sourceUrls[0]['url']);
            $detailPageDOM = $this->runCrawler($absoluteUrl);
        } catch (ChromeException $e) {
            return $this->assertContains('HTTP Response', $e->getMessage());
        } catch (Exception $e) {
            return $this->assertContains('HTTP Response', $e->getMessage());
        }

        foreach ($this->allowedAttributes as $attribute => $validation) {
            $this->assertEmpty(
                $this->websource->{'get' . ucfirst(camel_case($attribute))}($detailPageDOM),
                "Found a {$attribute} element on an invalid page. Define a more precise $attribute css selector to 
                avoid this. \n[Tested URL: {$invalidDetailPageUrl}]");
        }
    }

    protected function createInvalidUrl($url)
    {
        $invalidUrl = explode('/', preg_replace("/[0-9]{4}/", '999', $url));

        if (count($invalidUrl) > 6) {
            $invalidUrl[6] = str_rot13($invalidUrl[6]);
            $invalidUrl[5] = str_rot13($invalidUrl[5]);
            $invalidUrl[4] = str_rot13($invalidUrl[4]);
        }

        $invalidUrl[count($invalidUrl) - 1] = str_rot13($invalidUrl[count($invalidUrl) - 1]) . 'ooops123';

        return implode('/', $invalidUrl);
    }

    protected function runCrawler($url)
    {
		return Browse::browse($url, $this->crawlerSettings );
    }
}
