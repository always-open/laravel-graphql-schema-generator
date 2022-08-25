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

    'custom_input_definitions' => [
//        'datetime' => 'input DateTimeFilterInput {
//            ne: DateTime
//            eq: DateTime
//            le: DateTime
//            lt: DateTime
//            ge: DateTime
//            gt: DateTime
//            between: [DateTime]
//        }',
//        'date' => 'input DateFilterInput {
//            ne: Date
//            eq: Date
//            le: Date
//            lt: Date
//            ge: Date
//            gt: Date
//            between: [Date]
//        }',
    ],

    'model_path' => app_path('Models'),

    'model_stub' => __DIR__ . '/../stubs/graphql_schema.stub',

    'schema_path' => app_path('../graphql'),
];
