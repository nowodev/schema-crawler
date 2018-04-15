<?php

namespace SchemaCrawler\Adapters;

use SchemaCrawler\RawData;
use Illuminate\Validation\ValidationException;

abstract class Adapter
{
    protected $rawData = null;

    protected $options = [];

    protected $allowedAttributes = [];

    /**
     * Adapter constructor.
     * @param RawData $rawData
     * @param array   $options
     * @param \Config $config
     */
    public function __construct(RawData $rawData, array $options, \Config $config)
    {
        $this->rawData = $rawData;
        $this->options = $options;
        $this->allowedAttributes = $config->get('schema-crawler.attributes_to_crawl');
    }

    /**
     * @param $name
     * @param $arguments
     * @return bool
     */
    public function __call($name, $arguments)
    {
        $attribute = camel_case(str_replace('get', '', $name));
        if (!in_array($attribute, array_keys($this->allowedAttributes))) {
            return false;
        }

        return $this->rawData->{$attribute};
    }

    /**
     * @param String $input
     * @return null|string|string[]
     */
    protected function normalize(String $input)
    {
        return preg_replace('/[\s\s]+/u', ' ', trim(html_entity_decode($input), ".:=- \n\t\r\0\x0B\xC2\xA0"));
    }

    /**
     * @return array
     */
    protected function getAttributes()
    {
        $attributes = [];
        foreach (array_keys($this->allowedAttributes) as $attribute) {
            $getAttribute = camel_case('get_' . $attribute);
            $attributes[$attribute] = $this->$getAttribute();
        }

        return $attributes;
    }

    /**
     * @return array
     * @throws ValidationException
     */
    public function validateAndGetData()
    {
        $data = $this->getAttributes();
        $validator = \Validator::make($data, $this->allowedAttributes);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $data['url'] = $this->rawData->getUrl();

        return $data;
    }
}