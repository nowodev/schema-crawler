<?php

namespace SchemaCrawler\Browser;

use Symfony\Component\DomCrawler\Crawler;

abstract class AbstractBrowser
{
    /**
     * Specific options for the Browser.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Url to crawl
     *
     * @var url
     */
    protected $url = '';

    /**
     * Proxy constructor.
     *
     * @param array   $options

     */
    public function __construct($url, array $options)
    {
        $this->url = $url;
        $this->options = $options;
    }
    
    /**
     * return thr crawled page through proxy
     * @return  Crawler
     * 
     */
    public abstract function getDOMCrawler(): Crawler;

    /**
     * translate general options to Browser specific options
     * 
     */
    public function getOption($key, $defaultValue = null)
    {
		return $this->options[$key] ?? $defaultValue;
	}

    
}
