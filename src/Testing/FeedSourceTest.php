<?php

namespace SchemaCrawler\Testing;

use Illuminate\Foundation\Testing\TestCase;
use Prewk\XmlStringStreamer;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

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

    protected function setUp()
    {
        parent::setUp();

        $feedsourceClass = $this->sourceClass ?: config('schema-crawler.generator.feedsource.namespace') . '\\' . str_replace('Test', '', (new \ReflectionClass($this))->getShortName());
        $this->feedsource = new $feedsourceClass(null);
        $this->feedUrls = $this->feedsource->getFeedUrls();
        $this->pathSelectors = $this->feedsource->getPathSelectors();

        if (!file_exists(storage_path('schema-crawler/temp/testing')) && !is_dir(storage_path('schema-crawler/temp/testing'))) {
            mkdir(storage_path('schema-crawler/temp/testing'), 0777, true);
        }
    }

    /** @test */
    public function it_can_download_and_extract_the_feeds()
    {
        foreach ($this->feedUrls as $sourceFeed) {
            $zipped = array_get($sourceFeed, 'zipped', false);
            $filePath = storage_path('schema-crawler/temp/testing/') . md5(time()) . ($zipped ? '.gz' : '');

            try {
                file_put_contents($filePath, file_get_contents($sourceFeed['url']));
            } catch (\Exception $e) {
                $filePath = null;
            }

            $this->assertFileExists($filePath);

            if ($zipped) {
                $zip = new Process(['gzip', '-dk', $filePath]);

                $zip->run();

                if (!$zip->isSuccessful()) {
                    throw new ProcessFailedException($zip);
                }

                $this->assertFileExists($filePath);
                unlink(substr($filePath, 0, -3));
            }

            unlink($filePath);
        }
    }

    /** @test */
    public function it_can_get_the_attributes()
    {
        $zipped = array_get($this->feedUrls[0], 'zipped', false);
        $filePath = storage_path('schema-crawler/temp/testing/') . md5(time()) . ($zipped ? '.gz' : '');

        try {
            file_put_contents($filePath, file_get_contents($this->feedUrls[0]['url']));
        } catch (\Exception $e) {
            $filePath = null;
        }

        if ($zipped AND !empty($filePath)) {
            $zip = new Process(['gzip', '-dk', $filePath]);

            $zip->run();

            if (!$zip->isSuccessful()) {
                throw new ProcessFailedException($zip);
            }
        }

        $stream = XmlStringStreamer::createStringWalkerParser($zipped ? substr($filePath, 0, -3) : $filePath, [
            'expectGT' => true,
        ]);

        while ($node = trim($stream->getNode())) {
            if (!starts_with($node, ('<' . $this->feedUrls[0]['schemaNode']))) {
                continue;
            }
            $crawler = new Crawler(str_replace(['<![CDATA[', ']]>'], '', $node));

            foreach ($this->pathSelectors as $attribute => $path) {
                if (!empty($path)) {
                    $this->assertGreaterThan(0, $crawler->filter($path)->count());
                }
            }
            break;
        }

        unlink(substr($filePath, 0, -3));
        unlink($filePath);
    }
}