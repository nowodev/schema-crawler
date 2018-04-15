<?php

namespace SchemaCrawler\Containers;

class RawData
{
    protected $url;

    /**
     * RawData constructor.
     *
     * @param $url
     */
    public function __construct($url)
    {
        $this->url = $url;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }
}