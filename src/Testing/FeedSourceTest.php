<?php


namespace SchemaCrawler\Testing;

use ChromeHeadless\ChromeHeadless;
use Illuminate\Foundation\Testing\TestCase;
use SchemaCrawler\Helper\Helper;

abstract class FeedSourceTest extends TestCase
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
    protected $feedsource;

    protected $pathSelectors;

    protected $feedUrls = [];

    protected $allowedAttributes;

    protected function setUp()
    {
        parent::setUp();

        $feedsourceClass = $this->sourceClass ?: config('schema-crawler.generator.feedsource.namespace') . '\\' . str_replace('Test', '', (new \ReflectionClass($this))->getShortName());
        $this->feedsource = new $feedsourceClass(null);
        $this->feedUrls = $this->feedsource->getFeedUrls();
        $this->pathSelectors = $this->feedsource->getPathSelectors();
        $this->allowedAttributes = config('schema-crawler.attributes_to_crawl');

        if (!file_exists(storage_path('schema-crawler/temp/testing')) && !is_dir(storage_path('schema-crawler/temp/testing'))) {
            mkdir(storage_path('schema-crawler/temp/testing'), 0777, true);
        }

    }

    /** @test */
    public function it_can_download_the_feeds()
    {
        foreach ($this->feedUrls as $sourceFeed) {

            $filePath = storage_path('schema-crawler/temp/testing/') . md5(time());

            try {
                file_put_contents($filePath, file_get_contents($sourceFeed['url']));
            } catch (\Exception $e) {
                $filePath = null;
            }

            $this->assertFileExists($filePath);

            unlink($filePath);
        }
    }
}