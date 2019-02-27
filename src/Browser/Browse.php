<?php

namespace SchemaCrawler\Browser;

use Symfony\Component\DomCrawler\Crawler;
use SchemaCrawler\Exceptions\BrowserException;

class Browse
{
   
	public static function browse($url, $crawlerSetting)
	{
		$browser = ucfirst(camel_case($crawlerSetting['type']));
		$browserClass = '\\SchemaCrawler\Browser\\Browsers\\'.$browser;
		$retry = $crawlerSetting['retry'] ?? 1;
		$retry = (((int) $retry) < 1) ? 1 : $retry;
		for($i = 0; $i < $retry; $i++){
			try
			{
				$crawler = (new $browserClass($url, $crawlerSetting))->getDOMCrawler();
				break;
			}catch(BrowserException $e){
				$error = $e->getMessage();
			}
		}
		if(isset($error))
			throw new BrowserException($error);
			
		return $crawler;
	}
    
}
