<?php

namespace AlwaysOpen\LaravelGraphqlSchemaGenerator;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use AlwaysOpen\LaravelGraphqlSchemaGenerator\Commands\LaravelGraphqlSchemaGeneratorCommand;

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
