<?php

namespace SchemaCrawler\Models;

interface Schema
{
    /**
     * @return array
     */
    public static function getUniqueKeys(): array;

    /**
     * @param array $data
     * @return mixed
     */
    public static function createFromCrawlerData(array $data);

    /**
     * @param array $data
     * @return mixed
     */
    public function updateFromCrawlerData(array $data);
}