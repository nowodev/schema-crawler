<?php


namespace SchemaCrawler\Testing;

use ChromeHeadless\ChromeHeadless;
use Illuminate\Foundation\Testing\TestCase;
use SchemaCrawler\Helper\Helper;

abstract class SourceTest extends TestCase
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

    protected $sourceUrls;

    protected $allowedAttributes;

    protected function setUp()
    {
        parent::setUp();
        $websourceClass = $this->sourceClass ?: config('schema-crawler.generator.websource.namespace') . '\\' . str_replace('Test', '', (new \ReflectionClass($this))->getShortName());
        $this->websource = new $websourceClass(null);
        $this->sourceUrls = $this->websource->getSourceUrls();
        $this->cssSelectors = $this->websource->getCssSelectors();
        $this->allowedAttributes = config('schema-crawler.attributes_to_crawl');
    }

    /** @test */
    public function it_can_get_schema_urls_from_source_pages()
    {
        foreach ($this->sourceUrls as $sourcePage) {
            $sourcePageDOM = ChromeHeadless::url($sourcePage['url'])->getDOMCrawler();
            $this->assertGreaterThan(0, $sourcePageDOM->filter($this->cssSelectors['overview']['detailPageLink'])
                ->count(), "Couldn't find any schema urls.\n[Tested URL: " . $sourcePage['url'] . "]");
        }
    }

    /** @test */
    public function it_can_get_the_paging()
    {
        if (empty($this->cssSelectors['overview']['nextPageLink'])) {
            return $this->assertTrue(true); // this source has no paging enabled
        }

        foreach ($this->sourceUrls as $sourcePage) {
            $sourcePageDOM = ChromeHeadless::url($sourcePage['url'])->getDOMCrawler();
            if ($sourcePageDOM->filter($this->cssSelectors['overview']['nextPageLink'])->count() > 0) {
                return $this->assertTrue(true);
            }
        }

        $this->fail("Couldn't find a paging element on any source url.");
    }

    /** @test */
    public function it_can_get_the_required_attributes()
    {
        $sourcePageDOM = ChromeHeadless::url($this->sourceUrls[0]['url'])->getDOMCrawler();
        $detailPageUrl = $sourcePageDOM->filter($this->cssSelectors['overview']['detailPageLink'])->attr('href');
        $detailPageDOM = ChromeHeadless::url(Helper::generateAbsoluteUrl($detailPageUrl, $this->sourceUrls[0]['url']))
            ->getDOMCrawler();

        foreach ($this->allowedAttributes as $attribute => $validation) {
            if (str_contains($validation, 'required')) {
                $this->assertNotEmpty($this->websource->{'get' . ucfirst(camel_case($attribute))}($detailPageDOM),
                    "Couldn't find any $attribute element.\n[Tested URL: $detailPageUrl]");
            }
        }
    }

    /** @test */
    public function it_does_not_get_schema_urls_from_invalid_sources()
    {
        $invalidSourceUrl = str_replace_last('/', '/ooops123', $this->sourceUrls[0]['url']);
        $sourcePageDOM = ChromeHeadless::url($invalidSourceUrl)->getDOMCrawler();
        $this->assertEquals(0, $sourcePageDOM->filter($this->cssSelectors['overview']['detailPageLink'])
            ->count(), "Found schema urls on an invalid source. Define a more precise page link css selector to avoid this.\n[Tested URL: $invalidSourceUrl]");
    }

    /** @test */
    public function it_does_not_get_attributes_from_invalid_pages()
    {
        $sourcePageDOM = ChromeHeadless::url($this->sourceUrls[0]['url'])->getDOMCrawler();
        $invalidDetailPageUrl = str_replace_last('/', '/ooops123', $sourcePageDOM->filter($this->cssSelectors['overview']['detailPageLink'])
            ->attr('href'));
        $detailPageDOM = ChromeHeadless::url(Helper::generateAbsoluteUrl($invalidDetailPageUrl, $this->sourceUrls[0]['url']))
            ->getDOMCrawler();

        foreach ($this->allowedAttributes as $attribute => $validation) {
            $this->assertEmpty($this->websource->{'get' . ucfirst(camel_case($attribute))}($detailPageDOM),
                "Found a $attribute element on an invalid page. Define a more precise $attribute css selector to avoid this.\n[Tested URL: $invalidDetailPageUrl]");
        }
    }
}