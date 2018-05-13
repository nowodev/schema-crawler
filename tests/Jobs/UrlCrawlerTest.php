<?php


namespace SchemaCrawler\Test\Jobs;


use Illuminate\Support\Facades\Queue;
use SchemaCrawler\Jobs\Web\UrlCrawler;
use SchemaCrawler\Jobs\Web\WebDetailCrawler;
use SchemaCrawler\Test\Support\TestSources\CoolBooks;
use SchemaCrawler\Test\TestCase;

class UrlCrawlerTest extends TestCase
{
    /** @test */
    public function it_dispatches_detail_crawlers()
    {
        $urls = [[
                     'url'                 => 'http://www.coolbookstore.com/products/21342354/cool-book-1',
                     'overwriteAttributes' => ['category' => ['crime', 'history']]
                 ],
                 [
                     'url'                 => 'http://www.coolbookstore.com/products/21342354/cool-book-2',
                     'overwriteAttributes' => ['category' => 'crime', 'children' => false]
                 ],
                 [
                     'url'                 => 'http://www.coolbookstore.com/products/21342354/cool-book-3',
                     'overwriteAttributes' => ['children' => false]
                 ],
                 [
                     'url'                 => 'http://www.coolbookstore.com/products/21342354/cool-book-4',
                     'overwriteAttributes' => []
                 ]
        ];

        Queue::fake();

        (new UrlCrawler(new CoolBooks(null)))->runDetailCrawlers($urls);

        foreach ($urls as $url) {
            Queue::assertPushed(WebDetailCrawler::class, function (WebDetailCrawler $job) use ($url) {
                return $job->getUrl() === $url['url'] AND $job->getOverwriteAttributes() === $url['overwriteAttributes'];
            });
        }
    }
}