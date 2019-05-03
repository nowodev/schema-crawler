<?php

namespace SchemaCrawler\Sources;

use SchemaCrawler\Jobs\Json\JsonCrawler;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class JsonSource
 * @package SchemaCrawler\Sources
 */
abstract class JsonSource extends Source
{
    /**
     * The urls of the feeds that contain the schema information.
     * Options for each url can be defined that will overwrite the attributes of the crawled schemas.
     *
     * @var array
     */
    protected $jsonUrls = [];

    /**
     * The path selectors of the attributes of the schema.
     *
     * @var array
     */
    protected $pathSelectors = [];


    /**
     * OverviewCrawler class
     * @var string
     */
    protected $overviewCrawlerClass = JsonCrawler::class;
    /**
     * Get the urls of the pages that contain links to the schemas.
     *
     * @return array
     */
    public function getJsonUrls(): array
    {
        return $this->jsonUrls;
    }

    /**
     * Get the CSS selectors of the paging and the attributes of the schema.
     *
     * @return array
     */
    public function getPathSelectors(): array
    {
        return $this->pathSelectors;
    }

    
    /**
     * Determone if the node should be crawled or not 
     * @return bool
     */
    public function shouldBeCrawled(array $data): bool
    {
		// e.g. ignore non-relevant categories
        return true;
    }

    /**
     * @return array
     */
    protected function getSections(): array
    {
       return $this->getJsonUrls();
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

        if (!in_array($attribute, array_merge($this->allowedAttributes, ['url']))) {
            // attribute does not exist
            return false;
        }

        $node = $arguments[0];
        if (!is_array($node)) {
            // the data hasn't been defined in the parameters
            return false;
        }

        $pathSelector = $this->pathSelectors[$attribute];

        // return null if no path selector is defined
        if (empty($pathSelector)) {
            return null;
        }

        // get the element of the DOM by the defined CSS selector
        $element = data_get($node, $pathSelector);

        // by default return the inner text of the selected DOM element
        return $element;
    }
}
