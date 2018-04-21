<?php

namespace SchemaCrawler\Models;

use Illuminate\Database\Eloquent\Builder;

trait Source
{
    /**
     * Get the crawler class name of the source.
     *
     * @return string
     */
    abstract public function getCrawlerClassName(): string;

    /**
     * Scope a query to only include sources that should be crawled.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeShouldBeCrawled(Builder $query): Builder
    {
        return $query;
    }

    /**
     * This function will be triggered after the urls of a source have been crawled.
     *
     * @param array $urls
     * @return mixed
     */
    public function urlsCrawledEvent(array $urls)
    {
        //
    }
}