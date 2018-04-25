<?php

namespace SchemaCrawler\Console;

use Illuminate\Console\Command;
use SchemaCrawler\SchemaCrawler;

class CrawlerStartCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawler:start {source?}';

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

        if (empty($source)) {
            return SchemaCrawler::run();;
        }

        SchemaCrawler::runSource($source);
    }
}
