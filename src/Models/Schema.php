<?php

namespace SchemaCrawler\Models;

trait Schema
{
    /**
     * @return array
     */
    abstract public static function getUniqueKeys(): array;

    /**
     * @param array $data
     * @return mixed
     */
    abstract public static function createFromCrawlerData(array $data);

    /**
     * @param array $data
     * @return mixed
     */
    abstract public function updateFromCrawlerData(array $data);
}