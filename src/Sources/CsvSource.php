<?php

namespace SchemaCrawler\Sources;

use SchemaCrawler\Jobs\Web\CsvCrawler;

abstract class CsvSource extends Source
{
    /**
     * The urls of the feeds that contain the schema information.
     * Options for each url can be defined that will overwrite the attributes of the crawled schemas.
     *
     * @var array
     */
    protected $csvUrls = [];

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
     * Csv character controls options
     *
     * @var array
     */
    protected $csvOptinos = [
        'headerOffset'  => 0,
        'delimiter'     => ',',
        'enclosure'     => '"',
        'escape'        => '\\',
        'outputBOM'     => null,
        'streamFilter'  => null,
    ];

    /**
     * Get the urls of the pages that contain links to the schemas.
     *
     * @return array
     */
    public function getCsvUrls(): array
    {
        return $this->csvUrls;
    }

    /**
     * Get the array keys.
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
    public function shouldBeCrawled(array $record): bool
    {
        // e.g. ignore non-relevant categories
        return true;
    }

    public function getOption($name, $default = null)
    {
        return data_get($this->csvOptinos, $name, $default);
    }

    /**
     * Start the crawling process.
     *
     * @return mixed
     */
    public function run()
    {
        dispatch(new CsvCrawler($this));
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

        $record = $arguments[0];
        if (!is_array($record)) {
            // the record hasn't been defined in the parameters
            return false;
        }

        $pathSelector = $this->pathSelectors[$attribute];

        // return null if no path selector is defined
        if (empty($pathSelector)) {
            return null;
        }

        return data_get($record, $pathSelector, null);

    }
}
