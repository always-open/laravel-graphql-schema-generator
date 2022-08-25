<?php

namespace AlwaysOpen\LaravelGraphqlSchemaGenerator;

use AlwaysOpen\LaravelGraphqlSchemaGenerator\Commands\LaravelGraphqlSchemaGeneratorCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelGraphqlSchemaGeneratorServiceProvider extends PackageServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../stubs/graphql_schema.stub' => base_path('stubs/graphql_schema.stub'),
            ], 'graphql-stubs');
        }

        return parent::boot();
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-graphql-schema-generator')
            ->hasConfigFile('laravel-graphql-schema-generator')
            ->hasCommand(LaravelGraphqlSchemaGeneratorCommand::class);
    }
}
