
[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/support-ukraine.svg?t=1" />](https://supportukrainenow.org)

# Generate GraphQL schema from existing Laravel models/database

[![Latest Version on Packagist](https://img.shields.io/packagist/v/always-open/laravel-graphql-schema-generator.svg?style=flat-square)](https://packagist.org/packages/always-open/laravel-graphql-schema-generator)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/always-open/laravel-graphql-schema-generator/run-tests?label=tests)](https://github.com/always-open/laravel-graphql-schema-generator/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/always-open/laravel-graphql-schema-generator/Fix%20PHP%20code%20style%20issues?label=code%20style)](https://github.com/always-open/laravel-graphql-schema-generator/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/always-open/laravel-graphql-schema-generator.svg?style=flat-square)](https://packagist.org/packages/always-open/laravel-graphql-schema-generator)

This package will generate a GraphQL schema from your existing Laravel models and database. It reads through the 
existing models, relationships, and database to generate a GraphQL schema and optionally queries for each model. You can
also specify additional models such as vendor/packages models to be included.

## Installation

You can install the package via composer:

```bash
composer require always-open/laravel-graphql-schema-generator
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-graphql-schema-generator-config"
```

This is the contents of the published config file:

```php
return [
    'custom_type_mappings' => [
         'timestamp' => 'DateTime',
         'datetime'  => 'DateTime',
         'date'      => 'Date',
    ],

    'custom_scalar_definitions' => [
        'scalar Date @scalar(class: "Nuwave\\Lighthouse\\Schema\\Types\\Scalars\\Date")',
        'scalar DateTime @scalar(class: "Nuwave\\Lighthouse\\Schema\\Types\\Scalars\\DateTime")',
    ],

    'model_path' => app_path('Models'),

    'model_stub' => __DIR__ . '/../stubs/graphql_schema.stub',

    'schema_path' => app_path('../graphql'),
];
```

## Usage

### Basic/default usage

This command will output the GraphQL schema to the `schema_path` specified in the config file. It will only generate the 
schema file and will only parse the models in the `model_path` specified in the config file.

```bash
php artisan laravel-graphql-generator:create-schema
```

### Outputting queries

To also add queries to the schema, pass the `--include-queries` flag. This will generate a GraphQL schema file and query
files for each model in the `model_path` specified in the config file.

```bash
php artisan laravel-graphql-generator:create-schema --include-queries
```

### Adding additional models

If there are additional models that exist outside of the `model_path` that you want to include, you can pass the 
`--additional-models` flag. This is very useful if you have vendor/package models that you want to include.

```bash
php artisan laravel-graphql-generator:create-schema --additional-models="\\Spatie\\Tags\\Tag"
```

You can also pass in a comma separated list of models to include.

```bash
php artisan laravel-graphql-generator:create-schema --additional-models="\\Spatie\\Tags\\Tag","\\Spatie\\Activitylog\\Models\\Activity"
```
 

### Adding additional query properties

The default queries will be created using primary keys and unique indices. If you want to add additional properties to 
search you can pass them in using the `--additional-query-properties` flag. It will be applied to every model that has 
the specified property.

This command could add queries using the properties `key` and `name`. Support for multiple fields isn't supported by the 
pass in as of this version.

```bash
php artisan laravel-graphql-generator:create-schema --additional-query-properties=key,name
```


## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/qschmick/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [quentin.schmick](https://github.com/qschmick)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
