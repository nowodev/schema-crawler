<?php


namespace SchemaCrawler\Test\Support\TestSources;

use SchemaCrawler\Sources\WebSource;

class GoodBooks extends WebSource
{
    /**
     * The urls of the pages that contain links to the schemas.
     * Options for each url can be defined that will overwrite the attributes of the crawled schemas.
     *
     * @var array
     */
    protected $sourceUrls = [
        [
            'url' => 'https://www.goodbooksstore.com/all-books'
        ],
    ];

    /**
     * The CSS selectors of the paging and the attributes of the schema.
     *
     * @var array
     */
    protected $cssSelectors = [
        'overview' => [
            'detailPageLink' => '.product-name a',
            'nextPageLink'   => null,
        ],
        'detail'   => [
            'title'    => '.title',
            'author'   => '.author',
            'isbn'     => '.isbn',
            'category' => '.category'
        ],
    ];
}