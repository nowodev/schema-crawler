<?php

namespace SchemaCrawler\Browser\Browsers;

use SchemaCrawler\Browser\AbstractBrowser;
use SchemaCrawler\Exceptions\BrowserException;
use Symfony\Component\DomCrawler\Crawler;

class Scraperapi extends AbstractBrowser
{
    /**
     * return thr crawled page through proxy
     * @return  Crawler
     * 
     */
    public function getDOMCrawler(): Crawler
    {
		 return $this->apiRequest($this->url);
	}
	
	protected function apiRequest($url)
	{
		$render = $this->getOption('render_script', false);
		$url = implode('', [
            'http://api.scraperapi.com?key=',
            config('schema-crawler.scraperapi_key'),
            $render === true ? '&render=true' : '',
            '&url=',
            urlencode($url),
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/json"]);
        $response = curl_exec($ch);
        curl_close($ch);
		if( !empty( json_decode($response, true)))
			throw new BrowserException(json_decode($response, true)['error']);
			
        return new Crawler($response);
		
	}

    
}
