<?php


namespace SchemaCrawler\Test\Models;

use SchemaCrawler\Sources\WebSource;
use SchemaCrawler\Test\Support\TestModels\Bookstore;
use SchemaCrawler\Test\TestCase;

class SourceTest extends TestCase
{
    /** @test */
    public function it_can_find_and_create_the_source_class()
    {
        $sourceClassName = Bookstore::first()->getCrawlerClassName();

        $source = new $sourceClassName(null);

        $this->assertInstanceOf(WebSource::class, $source);
    }

    /** @test */
    public function it_can_set_if_a_source_should_be_crawled()
    {
        $source = Bookstore::first();

        $source->update(['active' => false]);

        $this->assertFalse(Bookstore::shouldBeCrawled()->where('id', $source->id)->exists());
    }
}