<?php

namespace SchemaCrawler\Testing;

use Illuminate\Foundation\Testing\TestCase;
use Prewk\XmlStringStreamer;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use League\Csv\Reader;
use League\Csv\Statement;
use SchemaCrawler\Sources\CsvSource;

abstract class CsvSourceTest extends TestCase
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
    protected $csvsource;

    protected $pathSelectors;

    protected $csvUrls = [];

    protected function setUp()
    {
        parent::setUp();

        $csvsourceClass = $this->sourceClass ?: config('schema-crawler.generator.feedsource.namespace') . '\\' . str_replace('Test', '', (new \ReflectionClass($this))->getShortName());
        $this->csvsource = new $csvsourceClass(null);
        $this->csvUrls = $this->csvsource->getCsvUrls();
        $this->pathSelectors = $this->csvsource->getPathSelectors();

        if (!file_exists(storage_path('schema-crawler/temp/testing')) && !is_dir(storage_path('schema-crawler/temp/testing'))) {
            mkdir(storage_path('schema-crawler/temp/testing'), 0777, true);
        }
    }

    /** @test */
    public function it_can_download_and_extract_the_csv()
    {
        foreach ($this->csvUrls as $sourceCsv) {
            $zipped = array_get($sourceCsv, 'zipped', false);
            $filePath = storage_path('schema-crawler/temp/testing/') . md5(time()) . ($zipped ? '.gz' : '');

            try {
                file_put_contents($filePath, file_get_contents($sourceCsv['url']));
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
        $zipped = array_get($this->csvUrls[0], 'zipped', false);
        $filePath = storage_path('schema-crawler/temp/testing/') . md5(time()) . ($zipped ? '.gz' : '');

        try {
            file_put_contents($filePath, file_get_contents($this->csvUrls[0]['url']));
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

        $csv = Reader::createFromPath($filePath, 'r');

        $csv->setDelimiter($this->csvsource->getOption('delimiter'));
        $csv->setEnclosure($this->csvsource->getOption('enclosure'));
        $csv->setEscape($this->csvsource->getOption('escape'));

        if($outputBOM = $this->csvsource->getOption('outputBOM'))
            $csv->setOutputBOM($outputBOM);

        if($streamFilter = $this->csvsource->getOption('streamFilter'))
            $csv->addStreamFilter($streamFilter);

        $csv->setHeaderOffset($this->csvsource->getOption('headerOffset') ); //set the CSV header offset

        $this->allowedAttributes['url'] = 'required';


        foreach ($csv as $offset => $record) {
            $detailPageUrl = $this->jsonsource->getUrl($data);
            foreach ($this->allowedAttributes as $attribute => $validation) {
                if (str_contains($validation, 'required')) {
                    $this->assertNotEmpty(
                        $this->csvsource->{'get' . ucfirst(camel_case($attribute))}($record),
                        "Couldn't find any {$attribute} element.\n[Tested URL: {$detailPageUrl}]");
                }
            }
            break;
        }
        unlink($filePath);
    }
}
