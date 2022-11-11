<?php

return [
    'custom_type_mappings' => [
//         'timestamp' => 'DateTime',
//         'datetime'  => 'DateTime',
//         'date'      => 'Date',
    ],

    'custom_scalar_definitions' => [
//        'scalar Date @scalar(class: "Nuwave\\\\Lighthouse\\\\Schema\\\\Types\\\\Scalars\\\\Date")',
//        'scalar DateTime @scalar(class: "Nuwave\\\\Lighthouse\\\\Schema\\\\Types\\\\Scalars\\\\DateTime")',
    ],

    'model_path' => app_path('Models'),

    'model_stub' => __DIR__ . '/../stubs/graphql_schema.stub',

    'schema_path' => app_path('../graphql'),

    'cacheable_models' => [
//        'App\\Models\\User' => [
//            'user_specific'     => false, // If true the cache is specific to the user
//            'max_cache_seconds' => 60,
//        ],
    ],
];
