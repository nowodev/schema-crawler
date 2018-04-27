<?php


namespace SchemaCrawler\Test\Helper;

use SchemaCrawler\Helper\Helper;
use SchemaCrawler\Test\TestCase;

class HelperTest extends TestCase
{
    /** @test */
    public function it_can_generate_an_absolute_url()
    {
        $urlsToBeTested = [
            [
                'relative' => '/products/21342354/cool-book-1',
                'absolute' => 'http://www.coolbookstore.com/somepage',
                'expected' => 'http://www.coolbookstore.com/products/21342354/cool-book-1'
            ],
            [
                'relative' => '/products/21342354/cool-book-2',
                'absolute' => 'https://www.coolbookstore.com',
                'expected' => 'https://www.coolbookstore.com/products/21342354/cool-book-2'
            ],
            [
                'relative' => 'http://www.coolbookstore.com/products/21342354/cool-book-3',
                'absolute' => 'http://www.coolbookstore.com/somepage',
                'expected' => 'http://www.coolbookstore.com/products/21342354/cool-book-3'
            ],
            [
                'relative' => 'http://www.coolbookstore.com/products/21342354/cool-book-4',
                'absolute' => 'http://www.coolbookstore.com/asd',
                'expected' => 'http://www.coolbookstore.com/products/21342354/cool-book-4'
            ],
        ];

        foreach ($urlsToBeTested as $url) {
            $this->assertEquals($url['expected'], Helper::generateAbsoluteUrl($url['relative'], $url['absolute']));
        }
    }

    /** @test */
    public function it_can_normalize_a_string()
    {
        $stringsToBeTested = [
            ' -&nbsp;This is a name'                    => 'This is a name',
            ".:=- \n\t\r\0\x0BThis is   a name\xC2\xA0" => 'This is a name',
            ' -&nbsp;This is a &nbsp;&nbsp;name&nbsp;'  => 'This is a name'
        ];

        foreach ($stringsToBeTested as $rawString => $expected) {
            $this->assertEquals($expected, Helper::normalize($rawString));
        }
    }

    /** @test */
    public function it_can_merge_duplicate_urls()
    {
        $urls = [
            [
                'url'     => 'http://www.coolbookstore.com/products/21342354/cool-book-1',
                'options' => ['category' => 'history']
            ],
            [
                'url'     => 'http://www.coolbookstore.com/products/21342354/cool-book-1',
                'options' => ['category' => 'crime']
            ],
            [
                'url'     => 'http://www.coolbookstore.com/products/21342354/cool-book-2',
                'options' => ['category' => 'crime']
            ],
            [
                'url'     => 'http://www.coolbookstore.com/products/21342354/cool-book-2',
                'options' => ['children' => false]
            ],
            [
                'url'     => 'http://www.coolbookstore.com/products/21342354/cool-book-3',
                'options' => ['children' => false]
            ],
            [
                'url'     => 'http://www.coolbookstore.com/products/21342354/cool-book-3',
                'options' => []
            ]
        ];

        $expected = [
            [
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
            ]
        ];

        $this->assertEquals($expected, Helper::mergeDuplicateUrls($urls));
    }
}