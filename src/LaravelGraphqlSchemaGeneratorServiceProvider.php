<?php

namespace AlwaysOpen\LaravelGraphqlSchemaGenerator;

use AlwaysOpen\LaravelGraphqlSchemaGenerator\Commands\LaravelGraphqlSchemaGeneratorCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelGraphqlSchemaGeneratorServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-graphql-schema-generator')
            ->hasConfigFile()
            ->hasCommand(LaravelGraphqlSchemaGeneratorCommand::class);
    }
}
