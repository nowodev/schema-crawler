<?php

namespace SchemaCrawler\Models;

use Illuminate\Database\Eloquent\Builder;

interface Source
{
    /**
     * @return string
     */
    public function getCrawlerClassName(): string;

    /**
     * Scope a query to only include sources that should be crawled.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeShouldBeCrawled(Builder $query): Builder;
}