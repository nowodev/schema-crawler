<?php


namespace SchemaCrawler\Test;


use Illuminate\Support\Facades\Queue;
use SchemaCrawler\Jobs\UrlCrawler;
use SchemaCrawler\SchemaCrawler;
use SchemaCrawler\Test\Support\TestModels\Bookstore;

class SchemaCrawlerTest extends TestCase
{
    /** @test */
    public function it_can_dispatch_multiple_crawlers()
    {
        Queue::fake();

        SchemaCrawler::run();

        foreach (Bookstore::shouldBeCrawled()->get() as $source) {
            Queue::assertPushed(UrlCrawler::class, function (UrlCrawler $job) use ($source) {
                return $job->source->getId() === $source->id;
            });
        }
    }

    /** @test */
    public function it_can_dispatch_a_single_crawler()
    {
        Queue::fake();

        $source = Bookstore::first();

        SchemaCrawler::runSource($source->id);

        Queue::assertPushed(UrlCrawler::class, function (UrlCrawler $job) use ($source) {
            return $job->source->getId() === $source->id;
        });
    }
}