<?php

namespace SchemaCrawler\Exceptions;

use SchemaCrawler\Containers\RawData;

class InvalidSchema extends \Exception
{
    /**
     * The validator instance.
     *
     * @var \Illuminate\Contracts\Validation\Validator
     */
    public $validator;

    /**
     * The crawled data of the website.
     *
     * @var RawData
     */
    protected $rawData;

    /**
     * The data that has been return by the adapter.
     *
     * @var array
     */
    protected $extractedData;

    /**
     * Create a new exception instance.
     *
     * @param  \Illuminate\Contracts\Validation\Validator $validator
     * @param                                             $rawData
     * @param                                             $extractedData
     */
    public function __construct($validator, RawData $rawData, array $extractedData = null)
    {
        parent::__construct($validator->errors()->first());
        $this->validator = $validator;
        $this->rawData = $rawData;
        $this->extractedData = $extractedData;
    }

    /**
     * Get the first validation error message.
     *
     * @return string
     */
    public function getFirstValidationError()
    {
        return $this->validator->errors()->first();
    }

    /**
     * Get the crawled data of the website.
     *
     * @return RawData
     */
    public function getRawData()
    {
        return $this->rawData;
    }

    /**
     * Get the data that has been return by the adapter.
     *
     * @return array
     */
    public function getExtractedData()
    {
        return $this->extractedData;
    }
}