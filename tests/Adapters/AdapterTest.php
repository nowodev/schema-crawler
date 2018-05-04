<?php


namespace SchemaCrawler\Test\Adapters;

use SchemaCrawler\Containers\RawData;
use SchemaCrawler\Exceptions\InvalidSchema;
use SchemaCrawler\Test\Support\TestAdapters\BookAdapter;
use SchemaCrawler\Test\TestCase;

class AdapterTest extends TestCase
{
    /** @test */
    public function it_can_get_the_attributes()
    {
        $rawData = new RawData('http://www.bookstore.com/product/1234', 1);

        $attributes = [
            'title'  => 'A good book title',
            'author' => 'John Doe',
            'isbn'   => '42352345623',
        ];

        foreach ($attributes as $key => $value) {
            $rawData->{$key} = $value;
        }

        $adapter = new BookAdapter($rawData, [], config('schema-crawler.attributes_to_crawl'));

        $this->assertEquals($attributes, $adapter->getAttributes());
    }

    /** @test */
    public function it_can_get_the_options()
    {
        $rawData = new RawData('http://www.bookstore.com/product/1234', 1);
        $option = ['convertIsbn' => true];

        $attributes = [
            'title'  => 'A good book title',
            'author' => 'John Doe',
            'isbn'   => '42352345623',
        ];

        foreach ($attributes as $key => $value) {
            $rawData->{$key} = $value;
        }

        $adapter = new BookAdapter($rawData, $option, config('schema-crawler.attributes_to_crawl'));

        $this->assertNotEquals($attributes, $adapter->getAttributes());
        $this->assertEquals($option['convertIsbn'], $adapter->getOption('convertIsbn'));
    }

    /** @test */
    public function it_adds_bail_to_validation_rules()
    {
        $rawData = new RawData('http://www.bookstore.com/product/1234', 1);

        $adapter = new BookAdapter($rawData, [], config('schema-crawler.attributes_to_crawl'));

        foreach ($adapter->getValidationRules() as $attribute => $rule) {
            $this->assertStringStartsWith('bail|', $rule);
        }
    }

    /** @test */
    public function it_can_validate_the_attributes()
    {
        $url = 'http://www.bookstore.com/product/1234';
        $sourceId = 1;
        $adapterOptions = ['convertIsbn' => true];

        $rawData = new RawData($url, $sourceId);

        $attributes = [
            'title'  => 'A good book title',
            'author' => 'John Doe',
            'isbn'   => '42352345623',
        ];

        foreach ($attributes as $key => $value) {
            $rawData->{$key} = $value;
        }

        $adapter = new BookAdapter($rawData, $adapterOptions, config('schema-crawler.attributes_to_crawl'));

        $attributes['isbn'] = '978' . $attributes['isbn'] . '3';

        $this->assertEquals(array_merge($attributes, compact('url', 'sourceId', 'adapterOptions')), $adapter->validateAndGetData());
    }

    /** @test */
    public function it_can_detect_invalid_attributes()
    {
        $url = 'http://www.bookstore.com/product/1234';
        $sourceId = 1;
        $adapterOptions = [];

        $rawData = new RawData($url, $sourceId);

        $attributes = [
            'title'  => 'A good book title',
            'author' => '',
            'isbn'   => '42352345623',
        ];

        foreach ($attributes as $key => $value) {
            $rawData->{$key} = $value;
        }

        $adapter = new BookAdapter($rawData, $adapterOptions, config('schema-crawler.attributes_to_crawl'));

        $this->expectException(InvalidSchema::class);
        $this->assertEquals(array_merge($attributes, compact('url', 'sourceId', 'adapterOptions')), $adapter->validateAndGetData());
    }
}