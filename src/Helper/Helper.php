<?php

namespace SchemaCrawler\Helper;

class Helper
{
    /**
     * Generate an absolute url.
     *
     * @param $url
     * @param $exampleUrl
     * @return string
     */
    public static function generateAbsoluteUrl($url, $exampleUrl)
    {
        $matches = [];
        preg_match("/^(http(s?):)\/\/[0-9A-z\.\-]+\//", $url, $matches);

        if (count($matches)) {
            return $url;
        }

        preg_match("/^(http(s?):)\/\/[0-9A-z\.\-]+/", $exampleUrl, $matches);

        return rtrim($matches[0], '/') . '/' . ltrim($url, '/');
    }

    /**
     * Trim a string.
     *
     * @param $input
     * @return null|string|string[]
     */
    public static function normalize($input)
    {
        return preg_replace('/[\s\s]+/u', ' ', trim(html_entity_decode($input), "·.:=- \n\t\r\0\x0B\xC2\xA0"));
    }

    /**
     * Merge duplicate urls from array.
     *
     * @param array $urls
     * @return array
     */
    public static function mergeDuplicateUrls(array $urls)
    {
        $newUrls = [];

        foreach ($urls as $el) {
            $key = array_search($el['url'], array_column($newUrls, 'url'));
            if ($key === false) {
                array_push($newUrls, $el);
                continue;
            }
            $newUrls[$key] = array_merge_recursive(array_filter($el), $newUrls[$key]);
            $newUrls[$key]['url'] = implode('', array_unique($newUrls[$key]['url']));
        }

        return $newUrls;
    }

    /**
     * Overwrite multiple values of an array.
     *
     * @param array $newValues
     * @param array $array
     * @return array
     */
    public static function overwriteArray(array $newValues, array $array)
    {
        foreach ($newValues as $key => $value) {
            $array[$key] = $value;
        }

        return $array;
    }

    /**
     * Crawl data using scraperapi.
     *
     * @param  string  $url
     * @return  \Symfony\Component\DomCrawler\Crawler
     */
    public static function scraperapiCrawl($url, $render = false)
    {
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

        return new \Symfony\Component\DomCrawler\Crawler($response);
    }
}