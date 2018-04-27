<?php


namespace SchemaCrawler\Test\Support\TestModels;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use SchemaCrawler\Models\Source;

class Bookstore extends Model
{
    use Source;

    protected $fillable = ['name', 'url', 'active'];

    /**
     * Get the crawler class name of the source.
     *
     * @return string
     */
    public function getCrawlerClassName(): string
    {
        return "SchemaCrawler\Test\Support\TestSources\\" . ucfirst(camel_case($this->name));
    }

    public function scopeShouldBeCrawled(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * This function will be triggered after the urls of a source have been crawled.
     *
     * @param array $urls
     * @return mixed
     */
    public function urlsCrawledEvent(array $urls)
    {
        return $urls;
    }
}