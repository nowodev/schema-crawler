<?php

namespace SchemaCrawler\Sources;

use Symfony\Component\DomCrawler\Crawler;

abstract class WebSource
{
    protected $id;

    protected $sourceUrls = [];

    protected $cssSelectors = [];

    protected $adapterOptions = [];

    protected $adapter = null;

    protected $schemaModel = null;

    protected $allowedAttributes = [];

    /**
     * WebSource constructor.
     * @param $sourceId
     */
    public function __construct($sourceId)
    {
        $this->id = $sourceId;
        $this->adapter = config('schema-crawler.default_adapter');
        $this->schemaModel = config('schema-crawler.schema_model');
        $this->allowedAttributes = array_keys(config('schema-crawler.attributes_to_crawl'));
    }

    /**
     * @return string
     */
    public function getSchemaModelClass(): string
    {
        return $this->schemaModel;
    }

    /**
     * @return array
     */
    public function getAdapterOptions(): array
    {
        return $this->adapterOptions;
    }

    /**
     * @return array
     */
    public function getSourceUrls(): array
    {
        return $this->sourceUrls;
    }

    /**
     * @return array
     */
    public function getCssSelectors(): array
    {
        return $this->cssSelectors;
    }

    /**
     * @return null
     */
    public function getAdapterClass()
    {
        return $this->adapter;
    }

    public function __call($name, $arguments)
    {
        $attribute = camel_case(str_replace('get', '', $name));
        if (!in_array($attribute, $this->allowedAttributes)) {
            return false;
        }

        $detailPage = $arguments[0];
        if (!$detailPage instanceof Crawler) {
            return false;
        }

        $element = $detailPage->filter($this->cssSelectors['detail'][$attribute]);

        return $element->count() ? $element->text() : null;
    }
}