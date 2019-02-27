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
    protected $signature = 'crawler:test {source?} {--timeout=60}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the tests for the sources.';

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
            if (ends_with($source, 'Test')) {
                $command[] = '--filter ' . $source;
            } else {
                $sourceClass = config('schema-crawler.source_model');
                $source = $sourceClass::where((new $sourceClass())->getRouteKeyName(), $source)->firstOrFail();
                $name = explode('\\', $source->getCrawlerClassName());
                $command[] = '--filter ' . array_pop($name) . 'Test';
            }
        } else {
            $command[] = str_replace(['\Tests', '\\'], [
                'tests',
                '/',
            ], config('schema-crawler.generator.websource.tests_namespace'));
        }

        (new Process(implode(' ', $command)))->setTimeout($this->option('timeout'))->run(function ($type, $buffer) {
            echo $buffer;
        });
    }
}
