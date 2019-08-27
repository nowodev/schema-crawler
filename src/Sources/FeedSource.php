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
     * Attributes that require multiple nodes to be collected.
     * e.g. sizes, usually xml feeds have several lines of the same product each with one diffrent size
     *
     * @var array
     */
    protected $groupedAttributes = [];

    /**
     * OverviewCrawler class
     * @var string
     */
    protected $overviewCrawlerClass = FeedCrawler::class;

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
     * Get grouped attibutes.
     *
     * @return array
     */
    public function getGroupedAttributes(): array
    {
        return $this->groupedAttributes;
    }

    /**
     * Determone if the node should be crawled or not
     * @return bool
     */
    public function shouldBeCrawled(Crawler $node): bool
    {
		// e.g. ignore non-relevant categories
        return true;
    }

    /**
     * @return array
     */
    protected function getSections() : array
    {
        return $this->getFeedUrls();
    }


    /**
     * Alternative method to download the feed (e.g. if it requires authentication)
     * @param $url
     * @return bool/string
     */
    public function getFeedContent($url)
    {
        return false;
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
