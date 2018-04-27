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
}