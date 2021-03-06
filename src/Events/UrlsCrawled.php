<?php


namespace SchemaCrawler\Events;


use Illuminate\Database\Eloquent\Model;
use SchemaCrawler\Models\Source;

/**
 * This event will be triggered after the urls of a source have been crawled.
 *
 * @package SchemaCrawler\Events
 */
class UrlsCrawled
{
    /**
     * The urls that have been crawled.
     *
     * @var array
     */
    public $urls;

    /**
     * Source model.
     *
     * @var Source
     */
    public $source;

    /**
     * UrlsCrawled constructor.
     *
     * @param array $urls Urls that have been crawled.
     * @param Model $source Source model.
     */
    public function __construct(array $urls, Model $source)
    {
        $this->urls = $urls;
        $this->source = $source;
    }
}