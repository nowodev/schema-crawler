<?php


namespace SchemaCrawler\Test\Jobs;


use SchemaCrawler\Containers\RawData;
use SchemaCrawler\Jobs\Web\WebDetailCrawler;
use SchemaCrawler\Test\Support\TestAdapters\BookAdapter;
use SchemaCrawler\Test\Support\TestModels\Book;
use SchemaCrawler\Test\Support\TestSources\CoolBooks;
use SchemaCrawler\Test\TestCase;

class WebDetailCrawlerTest extends TestCase
{
    /** @test */
    public function it_can_find_an_existing_schema()
    {
        $data = ['isbn' => '9780099499381'];

        $book = (new WebDetailCrawler('http://www.coolbookstore.com/products/21342354/cool-book-1', [], new CoolBooks(1)))
            ->findExistingSchema(Book::class, $data);

        $this->assertEquals(Book::where('isbn', '9780099499381')->first(), $book);
    }

    /** @test */
    public function it_returns_null_if_no_schema_has_been_found()
    {
        $data = ['isbn' => '0000000000000'];

        $book = (new WebDetailCrawler('http://www.coolbookstore.com/products/21342354/cool-book-1', [], new CoolBooks(1)))
            ->findExistingSchema(Book::class, $data);

        $this->assertNull($book);
    }

    /** @test */
    public function it_can_generate_an_adapter()
    {

        $url = 'http://www.coolbookstore.com/products/21342354/cool-book-1';

        $data = new RawData($url, 1);

        $adapter = (new WebDetailCrawler($url, [], new CoolBooks(null)))->createAdapterFromData($data);

        self::assertInstanceOf(BookAdapter::class, $adapter);
    }
}