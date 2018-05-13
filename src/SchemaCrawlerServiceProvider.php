<?php

namespace SchemaCrawler;

use Illuminate\Support\ServiceProvider;
use SchemaCrawler\Console\AdapterMakeCommand;
use SchemaCrawler\Console\CrawlerStartCommand;
use SchemaCrawler\Console\CrawlerTestCommand;
use SchemaCrawler\Console\FeedSourceMakeCommand;
use SchemaCrawler\Console\WebSourceMakeCommand;
use SchemaCrawler\Console\WebSourceTestMakeCommand;
use SchemaCrawler\Exceptions\Handler;

class SchemaCrawlerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/schema-crawler.php' => config_path('schema-crawler.php'),
        ], 'config');

        /*
        $this->publishes([
            __DIR__ . '/../resources/Crawler' => app_path('Crawler'),
        ]);
        */

        $this->loadMigrationsFrom(__DIR__ . '/../resources/database/migrations');

        if (!file_exists(storage_path('schema-crawler/temp')) && !is_dir(storage_path('schema-crawler/temp'))) {
            mkdir(storage_path('schema-crawler/temp'), 0777, true);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/schema-crawler.php', 'schema-crawler');

        $this->commands([
            CrawlerStartCommand::class,
            CrawlerTestCommand::class,
            AdapterMakeCommand::class,
            WebSourceMakeCommand::class,
            FeedSourceMakeCommand::class,
            WebSourceTestMakeCommand::class,
        ]);
    }
}
