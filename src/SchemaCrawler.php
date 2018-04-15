<?php


namespace SchemaCrawler;


class SchemaCrawler
{
    protected $sources = null;

    public function __construct(\Config $config)
    {
        $sourceClass = $config->get('schema-crawler.source_model');
        $this->sources = $sourceClass::shouldBeCrawled()->get();
    }

    public static function run()
    {
        return (new static)->dispatchCrawlers();
    }

    protected function dispatchCrawlers()
    {
        foreach ($this->sources as $source) {
            $crawler = $source->getCrawlerClassName();
            dispatch(new $crawler());
        }
    }
}