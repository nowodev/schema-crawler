<?php

namespace SchemaCrawler\Jobs\Csv;

use League\Csv\Reader;
use League\Csv\Statement;
use SchemaCrawler\Jobs\OverviewCrawler;
use SchemaCrawler\Sources\CsvSource;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class CsvCrawler extends OverviewCrawler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The path selectors for the attributes.
     *
     * @var array
     */
    protected $pathSelectors = [];

    /**
     * The url of the previous node
     *
     * @var string
     */
    protected $previousUrl = '';

    /**
     * The OverwriteAttributes of the previous node
     *
     * @var string
     */
    protected $previousOverwriteAttributes = [];

    /**
     * Values of the grouped attributes
     *
     * @var array
     */
    protected $groupedValues = [];

    /**
     * Previous Record
     *
     * @var string
     */
    protected $previousRecord;

    /**
     * Create a new job instance.
     *
     * @param CsvSource $source
     */
    public function __construct(CsvSource $source)
    {
        parent::__construct($source);
        $this->pathSelectors = $source->getPathSelectors();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->source->getCsvUrls() as $csvFile) {

            $filePath = $this->download($csvFile['url'], array_get($csvFile, 'zipped', false));

            if (empty($filePath)) {
                continue;
            }

            $csv = Reader::createFromPath($filePath, 'r');

            $csv->setDelimiter($this->source->getOption('delimiter'));
            $csv->setEnclosure($this->source->getOption('enclosure'));
            $csv->setEscape($this->source->getOption('escape'));

            if($outputBOM = $this->source->getOption('outputBOM'))
                $csv->setOutputBOM($outputBOM);

            if($streamFilter = $this->source->getOption('streamFilter'))
                $csv->addStreamFilter($streamFilter);

            $csv->setHeaderOffset($this->source->getOption('headerOffset') ); //set the CSV header offset

            foreach ($csv as $offset => $record) {
                if($this->source->shouldBeCrawled($record)){
                    $this->runDetailCrawler($record, $csvFile['overwriteAttributes']);
                }
            }

            unlink($filePath);
        }

        $this->fireUrlEvent($this->urls);
    }

    private function download(string $url, bool $extract = false)
    {
        $filePath = storage_path('schema-crawler/temp/') . md5(time()) . '.csv';

        try {
            file_put_contents($filePath . ($extract ? '.gz' : ''), file_get_contents($url));
        } catch (\Exception $e) {
            $filePath = null;
        }

        if (!$extract OR empty($filePath)) {
            return $filePath;
        }

        $zip = new Process(['gzip', '-dk', $filePath . '.gz']);

        $zip->run();

        if (!$zip->isSuccessful()) {
            throw new ProcessFailedException($zip);
        }

        unlink($filePath . '.gz');
        return $filePath;
    }

    private function runDetailCrawler(array $record, $overwriteAttributes = [])
    {
        $url = trim($this->source->getUrl($record));

        if( !is_null($this->previousRecord) && $this->previousUrl !==  $url )
        {
            $this->urls[] = compact('url', 'overwriteAttributes');

            // run detail crawler for the previous node
            dispatch(new CsvDetailCrawler($this->previousUrl, $this->previousOverwriteAttributes,
                $this->source, $this->previousRecord, ($this->groupedValues[$this->previousUrl] ?? [])));
        }

        $this->previousOverwriteAttributes = $overwriteAttributes;
        $this->previousRecord = $record;
        $this->previousUrl = $url;
        $this->collectGroupedValues($url, $record);
    }

    protected function collectGroupedValues($url, array $record)
    {
        if( !empty( $this->source->getGroupedAttributes() ) )
            foreach($this->source->getGroupedAttributes() as $attr)
                $this->groupedValues[$url][$attr][] = $this->source->{'get'.ucfirst($attr)}($record);
    }
}
