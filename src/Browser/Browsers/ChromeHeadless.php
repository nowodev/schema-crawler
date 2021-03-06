<?php

namespace SchemaCrawler\Browser\Browsers;

use SchemaCrawler\Browser\AbstractBrowser;
use ChromeHeadless\Laravel\ChromeHeadless as LaravelChromeHeadless;
use Symfony\Component\DomCrawler\Crawler;

class ChromeHeadless extends AbstractBrowser
{
    /**
     * return thr crawled page through proxy
     * @return  Crawler
     * 
     */
    public function getDOMCrawler(): Crawler
    {
		 return LaravelChromeHeadless::url($this->url)
            ->setBlacklist($this->options['blacklist'])
            ->setExcluded($this->options['excluded'])
            ->getDOMCrawler();
	}

    
}
