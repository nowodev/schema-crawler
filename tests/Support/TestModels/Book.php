<?php


namespace SchemaCrawler\Test\Support\TestModels;


use Illuminate\Database\Eloquent\Model;
use SchemaCrawler\Models\Schema;

class Book extends Model
{
    use Schema;

    protected $fillable = ['title', 'author', 'category', 'isbn'];

    protected $casts = ['category' => 'array'];

    /**
     * Define the unique keys of the schema. These keys will be used to identify a schema.
     *
     * @return array
     */
    public static function getUniqueKeys(): array
    {
        return ['isbn'];
    }

    /**
     * This function will be called after the attributes of a schema have been crawled
     * and no existing schema has been found.
     *
     * @param array $data Attributes that have been crawled.
     */
    public static function createFromCrawlerData(array $data)
    {
        static::create($data);
    }

    /**
     * This function will be called after the attributes of the schema have been crawled.
     *
     * @param array $data Attributes that have been crawled.
     */
    public function updateFromCrawlerData(array $data)
    {
        $this->update([
            'category' => array_filter(array_unique(array_merge(array_get($data, 'category', []), $this->category)))
        ]);
    }
}