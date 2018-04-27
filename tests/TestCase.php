<?php


namespace SchemaCrawler\Test;

use Orchestra\Testbench\TestCase as BaseTestCase;
use SchemaCrawler\SchemaCrawlerServiceProvider;
use SchemaCrawler\Test\Support\TestAdapters\BookAdapter;
use SchemaCrawler\Test\Support\TestModels\Book;
use SchemaCrawler\Test\Support\TestModels\Bookstore;

abstract class TestCase extends BaseTestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    /**
     * Get package providers.  At a minimum this is the package being tested, but also
     * would include packages upon which our package depends, e.g. Cartalyst/Sentry
     * In a normal app environment these would be added to the 'providers' array in
     * the config/app.php file.
     *
     * @param  \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [SchemaCrawlerServiceProvider::class];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('schema-crawler', [
            'schema_model'   => Book::class,
            'source_model' => Bookstore::class,
            'default_adapter'    => BookAdapter::class,
            'attributes_to_crawl' => [
                'title' => 'required',
                'author' => 'required',
                'isbn' => 'required',
            ],
            'raw_validation'      => [
                 'title' => 'min:2'
            ],
        ]);
    }

    /**
     * Define the database setup.
     */
    protected function setUpDatabase()
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        Bookstore::create([
            'name' => 'Cool Books',
            'url'  => 'https://www.coolbooksstore.com/',
        ]);

        Bookstore::create([
            'name' => 'Good Books',
            'url'  => 'https://www.goodbooksstore.com/',
        ]);
    }
}