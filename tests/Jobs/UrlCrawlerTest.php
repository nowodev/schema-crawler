<?php


namespace SchemaCrawler\Test\Jobs;


use Illuminate\Support\Facades\Queue;
use SchemaCrawler\Jobs\DetailCrawler;
use SchemaCrawler\Jobs\UrlCrawler;
use SchemaCrawler\Test\Support\TestSources\CoolBooks;
use SchemaCrawler\Test\TestCase;

class UrlCrawlerTest extends TestCase
{
    /** @test */
    public function it_dispatches_detail_crawlers()
    {
        $urls = [[
                     'url'     => 'http://www.coolbookstore.com/products/21342354/cool-book-1',
                     'options' => ['category' => ['crime', 'history']]
                 ],
                 [
                     'url'     => 'http://www.coolbookstore.com/products/21342354/cool-book-2',
                     'options' => ['category' => 'crime', 'children' => false]
                 ],
                 [
                     'url'     => 'http://www.coolbookstore.com/products/21342354/cool-book-3',
                     'options' => ['children' => false]
                 ],
                 [
                     'url'     => 'http://www.coolbookstore.com/products/21342354/cool-book-4',
                     'options' => []
                 ]
        ];

        Queue::fake();

        (new UrlCrawler(new CoolBooks(null)))->runDetailCrawlers($urls);

        foreach ($urls as $url) {
            Queue::assertPushed(DetailCrawler::class, function (DetailCrawler $job) use ($url) {
                return $job->getUrl() === $url['url'] AND $job->getOptions() === $url['options'];
            });
        }
    }
}