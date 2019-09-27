<?php

namespace SchemaCrawler\Browser\Browsers;

use SchemaCrawler\Browser\AbstractBrowser;
use SchemaCrawler\Exceptions\BrowserException;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Client;
class Simple extends AbstractBrowser
{
    /**
     * return thr crawled page through proxy
     * @return  Crawler
     *
     */
    public function getDOMCrawler(): Crawler
    {
		 return $this->request($this->url);
	}

	protected function request($url)
	{
        $client = new Client();
        try{
			$response = $client->request('GET', $url, [
				'headers'  => [
					'Cache-Control' => 'no-cache',
					'User-Agent' => 'sneakers123/1.0'
				],
				'timeout'  => 60,
				'defaults' => ['verify' => false]
			]);
        }catch(\GuzzleHttp\Exception\ClientException $e)
        {
			 throw new BrowserException('HTTP Response '.$e->getMessage());
		}

        $status_code = $response->getStatusCode();

        if ($status_code != 200 && $status_code < 411) {
            throw new BrowserException($status_code, 'Page is not available anymore. HTTP Response ' . $status_code);
        }
        if ($status_code != 200 && $status_code >= 411) {
            throw new BrowserException($status_code, 'Could not fetch page. HTTP Response ' . $status_code );
        }
        return new Crawler((string) $response->getBody(), $url );
	}


}
