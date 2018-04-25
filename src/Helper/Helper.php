<?php


namespace SchemaCrawler\Helper;


class Helper
{
    public static function generateAbsoluteUrl($url, $exampleUrl)
    {
        $matches = [];
        preg_match("/^(http(s?):)?\/\/[0-9A-z\.\-]+\//", $url, $matches);

        if (count($matches)) {
            return $url;
        }

        preg_match("/^(http(s?):)?\/\/[0-9A-z\.\-]+\//", $exampleUrl, $matches);

        return $matches[0] . ltrim('/', $url);
    }
}