<?php


namespace SchemaCrawler\Exceptions;


class InvalidSchema extends \Exception
{
    /**
     * The validator instance.
     *
     * @var \Illuminate\Contracts\Validation\Validator
     */
    public $validator;

    /**
     * Create a new exception instance.
     *
     * @param  \Illuminate\Contracts\Validation\Validator $validator
     * @return void
     */
    public function __construct($validator)
    {
        parent::__construct($validator->errors()->first());

        $this->validator = $validator;
    }

    /**
     * Get all of the validation error messages.
     *
     * @return array
     */
    public function errors()
    {
        return $this->validator->errors()->messages();
    }
}