<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Schema Model
    |--------------------------------------------------------------------------
    |
    | Define which model should be handled as the default schema.
    |
    */
    'schema_model'        => \App\ExampleSchema::class,
    /*
    |--------------------------------------------------------------------------
    | Source Model
    |--------------------------------------------------------------------------
    |
    | Define which model should be handled as the web source.
    |
    */
    'source_model'        => \App\ExampleSource::class,
    /*
    |--------------------------------------------------------------------------
    | Default Adapter
    |--------------------------------------------------------------------------
    |
    | Define which adapter should be used by default.
    |
    */
    'default_adapter'     => \App\Crawler\Adapters\ExampleAdapter::class,
    /*
    |--------------------------------------------------------------------------
    | Attributes
    |--------------------------------------------------------------------------
    |
    | Define the name and specification of the attributes that should be
    | crawled. Check the Laravel documentation for available validation rules:
    | https://laravel.com/docs/master/validation#available-validation-rules
    |
    */
    'attributes_to_crawl' => [
        /*
        'name' => 'required',
        'price' => 'required|numeric'
        */
    ]
];
