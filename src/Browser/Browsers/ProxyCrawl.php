<?php

namespace SchemaCrawler\Browser\Browsers;

use SchemaCrawler\Browser\AbstractBrowser;
use SchemaCrawler\Exceptions\BrowserException;
use Symfony\Component\DomCrawler\Crawler;

class ProxyCrawl extends AbstractBrowser
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
		$token = $this->getOption('render_script') === true ? 
				config('schema-crawler.proxycrawl_js_token'):
				config('schema-crawler.proxycrawl_token');
		
		$query = http_build_query( array_merge( ['url'=>$url, 
			'format'=>'json', 'token'=>$token], array_except($this->options, ['render_script'])));

		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL, 'https://api.proxycrawl.com/?'.$query);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($ch);
		curl_close($ch);

		$json = json_decode($response,true);

		if( $json['pc_status'] === 200 && $json['original_status'] === 200 )
			return new Crawler($json['body']);
		else
			throw new BrowserException('proxycrawl error! status(o,pc): '.
				($json['original_status'] ?? '?').','.$json['pc_status'].' error: '.($json['error'] ?? '!?'));
	}

    
}
