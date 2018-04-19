<?php

namespace SchemaCrawler\Containers;

use Illuminate\Support\Facades\Validator;
use SchemaCrawler\Exceptions\InvalidSchema;

class RawData
{
    protected $url;
    protected $sourceId;

    /**
     * RawData constructor.
     *
     * @param string $url
     * @param        $sourceId
     */
    public function __construct(string $url, $sourceId)
    {
        $this->url = $url;
        $this->sourceId = $sourceId;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return mixed
     */
    public function getSourceId()
    {
        return $this->sourceId;
    }

    public function validate()
    {
        $validator = Validator::make((array) $this, config('schema-crawler.raw_validation'));

        if ($validator->fails()) {
            throw new InvalidSchema($validator, $this);
        }

        return $this;
    }
}