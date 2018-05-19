<?php

namespace SchemaCrawler\Adapters;

use SchemaCrawler\Containers\RawData;
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
     * The name and specification of the attributes that should be overwritten.
     *
     * @var array
     */
    protected $overwriteAttributes = [];

    /**
     * Adapter constructor.
     *
     * @param RawData $rawData
     * @param array   $options
     * @param array   $allowedAttributes
     * @param array   $overwriteAttributes
     */
    public function __construct(RawData $rawData, array $options, array $allowedAttributes, array $overwriteAttributes = [])
    {
        $this->rawData = $rawData;
        $this->options = $options;
        $this->allowedAttributes = $allowedAttributes;
        $this->overwriteAttributes = $overwriteAttributes;
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

        if (in_array($attribute, array_keys($this->overwriteAttributes))) {
            return $this->overwriteAttributes[$attribute];
        }

        return $this->rawData->{$attribute};
    }

    /**
     * Return all attributes.
     *
     * @return array
     */
    public function getAttributes()
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
        $validator = \Validator::make($data, $this->getValidationRules());
        if ($validator->fails()) {
            throw new InvalidSchema($validator, $this->rawData, $data);
        }

        $data['url'] = $this->rawData->getUrl();
        $data['sourceId'] = $this->rawData->getSourceId();
        $data['adapterOptions'] = $this->options;

        return $data;
    }

    /**
     * Get an adapter option by a key.
     *
     * @param string $key Option key.
     * @param null   $default Default value which should be returned if no key is found.
     * @return mixed Value of the option key.
     */
    public function getOption(string $key, $default = null)
    {
        return array_get($this->options, $key, $default);
    }

    /**
     * Get the validation rules.
     *
     * @return array
     */
    public function getValidationRules()
    {
        return $this->allowedAttributes;
    }

    private function addBailToValidationRules()
    {
        foreach ($this->allowedAttributes as $attribute => $validation) {
            $this->allowedAttributes[$attribute] = 'bail|' . $validation;
        }
    }
}