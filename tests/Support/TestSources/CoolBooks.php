<?php


namespace SchemaCrawler\Test\Support\TestSources;

use SchemaCrawler\Sources\WebSource;

class CoolBooks extends WebSource
{
    /**
     * The urls of the pages that contain links to the schemas.
     * Options for each url can be defined that will overwrite the attributes of the crawled schemas.
     *
     * @var array
     */
    protected $sourceUrls = [
        [
            'url'     => 'https://www.coolbooksstore.com/crime',
            'options' => [
                'category' => 'crime'
            ],
            'url'     => 'https://www.coolbooksstore.com/history',
            'options' => [
                'category' => 'history'
            ],
        ],
    ];

    /**
     * Options defined here will be accessible in the adapter.
     *
     * @var array
     */
    protected $adapterOptions = ['convertIsbn' => true];

    /**
     * The CSS selectors of the paging and the attributes of the schema.
     *
     * @var array
     */
    protected $cssSelectors = [
        'overview' => [
            'detailPageLink' => '.product-name a',
            'nextPageLink'   => '.pages a.next',
        ],
        'detail'   => [
            'title'    => '.title',
            'author'   => '.author',
            'isbn'     => '.isbn',
            'category' => null
        ],
    ];
}