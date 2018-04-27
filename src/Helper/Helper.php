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
     * @param String $input
     * @return null|string|string[]
     */
    public static function normalize(String $input)
    {
        return preg_replace('/[\s\s]+/u', ' ', trim(html_entity_decode($input), ".:=- \n\t\r\0\x0B\xC2\xA0"));
    }
}