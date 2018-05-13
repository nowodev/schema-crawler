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
    | crawled. The attributes will be validated after they have been modified
    | by the adapter.
    |
    | Check the Laravel documentation for all available validation rules:
    | https://laravel.com/docs/master/validation#available-validation-rules
    |
    */
    'attributes_to_crawl' => [
        /*
        'name' => 'required',
        'price' => 'required|numeric'
        */
    ],
    /*
    |--------------------------------------------------------------------------
    | Raw Data Validation
    |--------------------------------------------------------------------------
    |
    | Define the validation of the raw crawled data, before it will be
    | manipulated by the adapter.
    |
    */
    'raw_validation'      => [
        /*
         'name' => 'min:10'
        */
    ],
    /*
    |--------------------------------------------------------------------------
    | Generator Settings
    |--------------------------------------------------------------------------
    |
    | Define the defaults of the classes generated by the Artisan make command.
    |
    */
    'generator'           => [
        'websource' => [
            'parent_class' => \SchemaCrawler\Sources\WebSource::class,
            'namespace'    => '\App\Crawler\Sources',
            'tests_namespace' => '\Tests\Feature\Crawler\Sources'
        ],
        'feedsource' => [
            'parent_class' => \SchemaCrawler\Sources\FeedSource::class,
            'namespace'    => '\App\Crawler\Sources',
            'tests_namespace' => '\Tests\Feature\Crawler\Sources'
        ],
        'adapter'   => [
            'parent_class' => \SchemaCrawler\Adapters\Adapter::class,
            'namespace'    => '\App\Crawler\Adapters'
        ]
    ]
];
