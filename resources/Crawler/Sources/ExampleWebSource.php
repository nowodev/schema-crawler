<?php

namespace App\Crawler\Sources;

use SchemaCrawler\Sources\WebSource;

class ExampleWebSource extends WebSource
{
    protected $sourceUrls = [
        [
            'url'                 => 'https://www.wowcoolsource.com/products-1',
            'overwriteAttributes' => ['price' => 10], // price will be always 10 for schemas of this page
        ],
        [
            'url' => 'https://www.wowcoolsource.com/products-2',
        ],
    ];

    protected $cssSelectors = [
        'overview' => [
            'detailPageLink' => '.products a',
            'nextPageLink'   => '.pages a.next' // can be null if there is only one page
        ],
        'detail'   => [
            'name'  => '.product .name',
            'price' => '.product .price'
        ],
    ];

    // by default the adapter of the config file will be used, but you can overwrite it here
    protected $adapter = SneakerAdapter::class;

    // it's possible to pass parameters to the adapter
    protected $adapterOptions = ['trimName' => true];

    // define a custom function if it's not possible to get the attribute via a css selector
    public function getImageSrc(Crawler $crawler)
    {
        $element = $crawler->filter($this->cssSelectors['detail']['imageSrc']);
        return $element->count() ? $element->attr('src') : null;
    }
}