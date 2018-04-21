<?php

namespace SchemaCrawler\Models;

trait Schema
{
    /**
     * Define the unique keys of the schema. These keys will be used to identify a schema.
     *
     * @return array
     */
    abstract public static function getUniqueKeys(): array;

    /**
     * This function will be called after the attributes of a schema have been crawled
     * and no existing schema has been found.
     *
     * @param array $data Attributes that have been crawled.
     */
    abstract public static function createFromCrawlerData(array $data);

    /**
     * This function will be called after the attributes of the schema have been crawled.
     *
     * @param array $data Attributes that have been crawled.
     */
    abstract public function updateFromCrawlerData(array $data);
}