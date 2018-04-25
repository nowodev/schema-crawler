<?php

namespace SchemaCrawler\Console;

use Illuminate\Console\Command;
use SchemaCrawler\SchemaCrawler;
use Symfony\Component\Process\Process;

class CrawlerTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawler:test {source?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the schema crawler.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $source = $this->argument('source');
        $command = ['vendor/bin/phpunit'];

        if (!empty($source)) {
            if (ends_with('Test', $source)) {
                $command[] = '--filter ' . $source;
            } else {
                $sourceClass = config('schema-crawler.source_model');
                sourceClass::where((new $sourceClass())->getRouteKeyName(), $source)->firstOrFail();
                $command[] = '--filter ' . $source->getCrawlerClassName();
            }
        }

        return (new Process($command))->run();
    }
}
