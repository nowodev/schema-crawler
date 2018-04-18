<?php

namespace SchemaCrawler\Containers;

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
}