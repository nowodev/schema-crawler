<?php


namespace SchemaCrawler\Test\Container;

use SchemaCrawler\Containers\RawData;
use SchemaCrawler\Exceptions\InvalidSchema;
use SchemaCrawler\Test\TestCase;

class RawDataTest extends TestCase
{
    /** @test */
    public function it_can_validate_the_raw_attributes()
    {
        $rawData = new RawData('http://www.bookstore.com/product/1234', 1);

        $rawData->title = 'A good book title';
        $rawData->author = 'John Doe';
        $rawData->isbn = '42352345623';

        $this->assertEquals($rawData, $rawData->validate());
    }

    /** @test */
    public function it_can_detect_invalid_raw_attributes()
    {
        $rawData = new RawData('http://www.bookstore.com/product/1234', 1);

        $rawData->title = 'a';
        $rawData->author = 'John Doe';
        $rawData->isbn = '42352345623';

        $this->expectException(InvalidSchema::class);
        $rawData->validate();
    }
}