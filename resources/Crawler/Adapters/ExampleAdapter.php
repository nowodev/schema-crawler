<?php

namespace App\Crawler\Adapters;

use SchemaCrawler\Adapters\Adapter;

class ExampleAdapter extends Adapter
{
    /**
     * Manipulate the name of the crawled product.
     *
     * @return string
     */
    public function getName()
    {
        // you can access the crawled data via the attached rawData attribute
        $name = $this->rawData->name;

        if ($this->options['trimName']) {
            return trim($name);
        }

        return $name;
    }
}