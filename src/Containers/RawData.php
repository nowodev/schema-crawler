<?php

namespace SchemaCrawler\Containers;

use Illuminate\Support\Facades\Validator;
use SchemaCrawler\Exceptions\InvalidSchema;

class RawData
{
    /**
     * The url of the origin of the data.
     *
     * @var string
     */
    protected $url;

    /**
     * The database id of the source.
     *
     * @var
     */
    protected $sourceId;

    /**
     * RawData constructor.
     *
     * @param string $url Origin of the data
     * @param        $sourceId Database id of the source
     */
    public function __construct(string $url, $sourceId)
    {
        $this->url = $url;
        $this->sourceId = $sourceId;
    }

    /**
     * Get the url.
     *
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Get the database id of the source.
     *
     * @return mixed
     */
    public function getSourceId()
    {
        return $this->sourceId;
    }

    /**
     * Validate the data.
     *
     * @return $this
     * @throws InvalidSchema
     */
    public function validate()
    {
        $config = config('schema-crawler.raw_validation');
        $validator = Validator::make(array_intersect_key((array) $this, $config), $config);

        if ($validator->fails()) {
            throw new InvalidSchema($validator, $this);
        }

        return $this;
    }
}