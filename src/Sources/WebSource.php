<?php

namespace SchemaCrawler\Sources;

use Symfony\Component\DomCrawler\Crawler;

abstract class WebSource
{

    /**
     * The database id of the source.
     *
     * @var mixed
     */
    protected $id;

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
     * Options defined here will be accessible in the adapter.
     *
     * @var array
     */
    protected $adapterOptions = [];

    /**
     * The default adapter that will be used can be overwritten here.
     *
     * @var string
     */
    protected $adapter;

    /**
     * The class name of the schema model.
     *
     * @var string
     */
    protected $schemaModel;

    /**
     * The class name of the source model.
     *
     * @var string
     */
    protected $sourceModel;

    /**
     * The name and specification of the attributes that should be crawled.
     *
     * @var array
     */
    protected $allowedAttributes = [];

    /**
     * WebSource constructor.
     *
     * @param $sourceId The database id of the source.
     */
    public function __construct($sourceId)
    {
        $this->id = $sourceId;
        $this->adapter = config('schema-crawler.default_adapter');
        $this->schemaModel = config('schema-crawler.schema_model');
        $this->sourceModel = config('schema-crawler.source_model');
        $this->allowedAttributes = array_keys(config('schema-crawler.attributes_to_crawl'));
    }

    /**
     * Get the database id of the source.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the class name of the schema model.
     *
     * @return string
     */
    public function getSchemaModelClass(): string
    {
        return $this->schemaModel;
    }

    /**
     * Get the class name of the source model.
     *
     * @return string
     */
    public function getSourceModelClass(): string
    {
        return $this->sourceModel;
    }

    /**
     * Get the adapter options.
     *
     * @return array
     */
    public function getAdapterOptions(): array
    {
        return $this->adapterOptions;
    }

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
     * Get the adapter class name.
     *
     * @return string
     */
    public function getAdapterClass()
    {
        return $this->adapter;
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
     * Dynamic getters for the defined attributes.
     *
     * @param $name
     * @param $arguments
     * @return bool|null|string
     */
    public function __call($name, $arguments)
    {
        $attribute = camel_case(str_replace('get', '', $name));

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
            return $json->count() ? data_get(json_decode(str_replace("\n", "" ,$json->last()->text())), $cssSelector, null) : null;
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