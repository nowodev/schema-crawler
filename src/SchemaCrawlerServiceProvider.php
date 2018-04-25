<?php

namespace SchemaCrawler;

use Illuminate\Support\ServiceProvider;
use SchemaCrawler\Console\AdapterMakeCommand;
use SchemaCrawler\Console\CrawlerStartCommand;
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
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/schema-crawler.php', 'schema-crawler');

        $this->commands([
            CrawlerStartCommand::class,
            AdapterMakeCommand::class,
            WebSourceMakeCommand::class,
            WebSourceTestMakeCommand::class
        ]);
    }
}
