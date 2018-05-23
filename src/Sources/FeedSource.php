<?php

namespace SchemaCrawler\Sources;

use SchemaCrawler\Jobs\Web\FeedCrawler;
use Symfony\Component\DomCrawler\Crawler;

abstract class FeedSource extends Source
{
    /**
     * The urls of the feeds that contain the schema information.
     * Options for each url can be defined that will overwrite the attributes of the crawled schemas.
     *
     * @var array
     */
    protected $feedUrls = [];

    /**
     * The path selectors of the attributes of the schema.
     *
     * @var array
     */
    protected $pathSelectors = [];

    /**
     * Get the urls of the pages that contain links to the schemas.
     *
     * @return array
     */
    public function getFeedUrls(): array
    {
        return $this->feedUrls;
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
     * Start the crawling process.
     *
     * @return mixed
     */
    public function run()
    {
        dispatch(new FeedCrawler($this));
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
        if (!$node instanceof Crawler) {
            // the DOM hasn't been defined in the parameters
            return false;
        }

        $pathSelector = $this->pathSelectors[$attribute];

        // return null if no path selector is defined
        if (empty($pathSelector)) {
            return null;
        }

        // get the element of the DOM by the defined CSS selector
        $element = $node->filter($pathSelector);

        // by default return the inner text of the selected DOM element
        return $element->count() ? $element->first()->text() : null;
    }
}