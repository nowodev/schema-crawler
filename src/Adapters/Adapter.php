<?php

namespace SchemaCrawler\Adapters;

use SchemaCrawler\Containers\RawData;
use Illuminate\Validation\ValidationException;
use SchemaCrawler\Exceptions\InvalidSchema;

abstract class Adapter
{
    protected $rawData = null;

    protected $options = [];

    protected $allowedAttributes = [];

    /**
     * Adapter constructor.
     * @param RawData $rawData
     * @param array   $options
     * @param array   $allowedAttributes
     */
    public function __construct(RawData $rawData, array $options, array $allowedAttributes)
    {
        $this->rawData = $rawData;
        $this->options = $options;
        $this->allowedAttributes = $allowedAttributes;
        $this->addBailToValidationRules();
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
     * @throws InvalidSchema
     */
    public function validateAndGetData()
    {
        $data = $this->getAttributes();
        $validator = \Validator::make($data, $this->allowedAttributes);
        if ($validator->fails()) {
            throw new InvalidSchema($validator);
        }

        $data['url'] = $this->rawData->getUrl();
        $data['sourceId'] = $this->rawData->getSourceId();

        return $data;
    }

    private function addBailToValidationRules()
    {
        foreach ($this->allowedAttributes as $attribute => $validation) {
            $this->allowedAttributes[$attribute] = 'bail|' . $validation;
        }
    }
}