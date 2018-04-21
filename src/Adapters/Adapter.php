<?php

namespace SchemaCrawler\Adapters;

use SchemaCrawler\Containers\RawData;
use Illuminate\Validation\ValidationException;
use SchemaCrawler\Exceptions\InvalidSchema;

abstract class Adapter
{
    /**
     * The crawled data of the website.
     *
     * @var RawData
     */
    protected $rawData;

    /**
     * Specific options that can be handled in the adapter.
     *
     * @var array
     */
    protected $options = [];

    /**
     * The name and specification of the attributes that should be crawled.
     *
     * @var array
     */
    protected $allowedAttributes = [];

    /**
     * Adapter constructor.
     *
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
     * Dynamic getters for the attributes.
     *
     * @param $name
     * @param $arguments
     * @return bool
     */
    public function __call($name, $arguments)
    {
        $attribute = camel_case(str_replace('get', '', $name));
        if (!in_array($attribute, array_keys($this->allowedAttributes))) {
            // attributes doesn't exist
            return false;
        }

        return $this->rawData->{$attribute};
    }

    /**
     * Helper function to trim a string.
     *
     * @param String $input
     * @return null|string|string[]
     */
    protected function normalize(String $input)
    {
        return preg_replace('/[\s\s]+/u', ' ', trim(html_entity_decode($input), ".:=- \n\t\r\0\x0B\xC2\xA0"));
    }

    /**
     * Return all attributes.
     *
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
     * Validate the attributes and return them.
     *
     * @return array
     * @throws InvalidSchema
     */
    public function validateAndGetData()
    {
        $data = $this->getAttributes();
        $validator = \Validator::make($data, $this->allowedAttributes);
        if ($validator->fails()) {
            throw new InvalidSchema($validator, $this->rawData, $data);
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