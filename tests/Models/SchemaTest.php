<?php


namespace SchemaCrawler\Test\Models;

use SchemaCrawler\Test\Support\TestModels\Book;
use SchemaCrawler\Test\TestCase;

class SchemaTest extends TestCase
{
    /** @test */
    public function it_can_get_the_unique_keys()
    {
        $keys = Book::getUniqueKeys();

        $this->assertTrue(is_array($keys));
        $this->assertGreaterThan(0, count($keys));
    }
}