<?php


namespace SchemaCrawler\Test\Support\TestAdapters;

use SchemaCrawler\Adapters\Adapter;
use SchemaCrawler\Helper\Helper;

class BookAdapter extends Adapter
{
    /**
     * Manipulate the isbn of the crawled product.
     *
     * @return string
     */
    public function getIsbn()
    {
        $isbn = Helper::normalize($this->rawData->isbn);

        if ($this->getOption('convertIsbn', false)) {
            $isbn = '978' . $isbn . '3';
        }

        return $isbn;
    }
}