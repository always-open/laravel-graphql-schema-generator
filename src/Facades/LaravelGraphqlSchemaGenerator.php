<?php

namespace AlwaysOpen\LaravelGraphqlSchemaGenerator\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \AlwaysOpen\LaravelGraphqlSchemaGenerator\LaravelGraphqlSchemaGenerator
 */
class LaravelGraphqlSchemaGenerator extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \AlwaysOpen\LaravelGraphqlSchemaGenerator\LaravelGraphqlSchemaGenerator::class;
    }
}
