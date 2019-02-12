<?php

namespace SchemaCrawler\Sources;

use SchemaCrawler\Jobs\Web\UrlCrawler;
use Symfony\Component\DomCrawler\Crawler;

abstract class WebSource extends Source
{
    /**
     * The urls of the pages that contain links to the schemas.
     * Options for each url can be defined that will overwrite the attributes of the crawled schemas.
     *
     * @var array
     */
    protected $sourceUrls = [];

    /**
     * The CSS selectors of the paging and the attributes of the schema.
     *
     * @var array
     */
    protected $cssSelectors = [];

    /**
     * Web source specific crawler settings.      
     *
     * @var array
     */
    protected $crawlerSettings = [  
        'type'      => 'chrome_headless', 
        'blacklist' => [], 
        'excluded'  => [],
    ];

    /**
     * Get the urls of the pages that contain links to the schemas.
     *
     * @return array
     */
    public function getSourceUrls(): array
    {
        return $this->sourceUrls;
    }

    /**
     * Get the CSS selectors of the paging and the attributes of the schema.
     *
     * @return array
     */
    public function getCssSelectors(): array
    {
        return $this->cssSelectors;
    }

     /**
     * Get the shop specific crawler settings.
     *
     * @return array
     */
    public function getCrawlerSettings(): array
    {
        return $this->crawlerSettings;
    }

    /**
     * Custom function to get the schema urls.
     *
     * @return array
     */
    public function getCustomSchemaUrls(): array
    {
        return [];
    }

    /**
     * Start the crawling process.
     *
     * @return mixed
     */
    public function run()
    {
        dispatch(new UrlCrawler($this));
    }

    /**
     * Dynamic getters for the defined attributes.
     *
     * @param $name
     * @param $arguments
     * @return bool|null|string
     */
    public function __call($name, $arguments)
    {
        $attribute = snake_case(str_replace('get', '', $name));

        if (!in_array($attribute, $this->allowedAttributes)) {
            // attribute does not exist
            return false;
        }

        $detailPage = $arguments[0];
        if (!$detailPage instanceof Crawler) {
            // the DOM hasn't been defined in the parameters
            return false;
        }

        $detailSelector = $this->cssSelectors['detail'][$attribute];

        $cssSelector = is_array($detailSelector) ? array_keys($detailSelector)[0] : $detailSelector;
        $options = is_array($detailSelector) ? explode('|', array_values($detailSelector)[0]) : null;
        $htmlAttribute = $options ? implode('', array_diff($options, ['array', 'json'])) : null;

        // return null if no css selector is defined
        if (empty($cssSelector)) {
            return null;
        }

        // get attribute from json array
        if ($options AND in_array('json', $options)) {
            $json = $detailPage->filter('script[type*="json"]');
            return $json->count() ? data_get(json_decode(str_replace("\n", "", $json->last()->text())), $cssSelector, null) : null;
        }

        // get the element of the DOM by the defined CSS selector
        $element = $detailPage->filter($cssSelector);

        // return null if no element has been found
        if ($element->count() == 0) {
            return null;
        }

        // if attribute has been defined as array in the options, return all elements
        if ($options AND in_array('array', $options)) {
            return $element->each(function (Crawler $node) use ($htmlAttribute) {
                return $htmlAttribute ? $node->attr($htmlAttribute) : $node->text();
            });
        }

        // by default return the inner text of the selected DOM element
        return $htmlAttribute ? $element->first()->attr($htmlAttribute) : $element->first()->text();
    }
}