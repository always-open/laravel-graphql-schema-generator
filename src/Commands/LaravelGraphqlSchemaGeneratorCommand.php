<?php

namespace AlwaysOpen\LaravelGraphqlSchemaGenerator\Commands;

use AlwaysOpen\Sidekick\Console\Traits\ArrayOption;
use AlwaysOpen\Sidekick\Console\Traits\IndentOutput;
use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ReflectionClass;
use Symfony\Component\Finder\SplFileInfo;

class LaravelGraphqlSchemaGeneratorCommand extends Command
{
    use ArrayOption;
    use IndentOutput;

    const COMMAND_NAME = 'laravel-graphql-generator:create-schema';

    /**
     * @var string
     */
    protected $signature = self::COMMAND_NAME . '
    {--include-queries : When set the command will build a base line query file for each model.}
    {--additional-query-properties= : List of properties that will be used to identify a Query beyond unique and primary key if they exist on the model.}
    {--additional-models= : List of models to include that might not be part of the core directory such as vendor models.}';

    /**
     * @var string
     */
    protected $description = 'Create graphql schema from existing models';

    protected array $customTypeMappings = [];

    /**
     * @throws \Exception
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Building schema...');

        $schema = '';

        $this->getModels()
            ->merge($this->buildAdditionalModels())
            ->each(function (string $modelName) use (&$schema) {
                $this->indentedInfo('Building model: ' . $modelName);
                $schema .= $this->buildGraphSchema(app($modelName));
            });

        $this->persistSchema($schema);

        return self::SUCCESS;
    }

    public function buildAdditionalModels() : Collection
    {
        $models = collect();

        foreach ($this->arrayOption('additional-models') as $additionalModel) {
            if (class_exists($additionalModel)) {
                $models->put($models->count(), $additionalModel);
            }
        }

        return $models;
    }

    public function getModels(): Collection
    {
        $models = collect(File::allFiles(config('laravel-graphql-schema-generator.model_path', app_path('Models'))))
            ->map(function (SplFileInfo $item) {
                $path = $item->getRelativePathName();

                return sprintf(
                    '\%s%s%s',
                    Container::getInstance()->getNamespace(),
                    'Models\\',
                    strtr(substr($path, 0, strrpos($path, '.')), '/', '\\'),
                );
            })
            ->filter(function ($class) {
                $valid = false;

                if (class_exists($class)) {
                    $reflection = new ReflectionClass($class);
                    $valid = ! str_ends_with($class, 'AuditLog')
                        && $reflection->isSubclassOf(Model::class)
                        && ! $reflection->isAbstract();
                }

                return $valid;
            });

        return $models->values();
    }

    public function buildGraphSchema(Model $model) : string
    {
        $schema = '';

        if (Schema::hasTable($model->getTable())) {
            $modelType = class_basename($model);

            $schema_columns = implode(PHP_EOL . '    ', $this->getTableColumnsAsSchemaColumns($model));

            $schema .= <<<SCHEMA
type $modelType implements Node {
    $schema_columns
}


SCHEMA;

            if ($this->option('include-queries')) {
                $this->buildGraphModelQuery($model);
            }
        }

        return $schema;
    }

    public function buildGraphModelQuery(Model $model) : void
    {
        $querySearchProperties = $this->arrayOption('additional-query-properties');
        $querySchema = 'extend type Query {' . PHP_EOL;
        $queries = [];

        $modelTableColumns = $this->getModelColumns($model);

        $modelBaseName = class_basename($model);

        collect(DB::select('SHOW INDEX FROM ' . $model->getTable()))
            ->filter(function (\stdClass $index) use ($querySearchProperties) {
                return ! $index->Non_unique || in_array($index->Column_name, $querySearchProperties);
            })
            ->groupBy('Key_name')
            ->each(function (Collection $keys) use ($model, $modelTableColumns, &$querySchema, $modelBaseName) {
                $queryDefinition = "find{$modelBaseName}By" . $keys->pluck('Column_name')
                        ->map(function ($name) {
                            return Str::studly($name);
                        })
                        ->implode('And')
                    . '(';
                $keyCount = 0;
                $count = $keys->count();

                $keys->each(function (\stdClass $keyIndex) use (&$queryDefinition, $modelTableColumns, $model, &$keyCount, $count) {
                    $filtered = array_filter($modelTableColumns, function (\stdClass $column) use ($keyIndex) {
                        return $column->Field === $keyIndex->Column_name;
                    });

                    $found = array_pop($filtered);

                    if (! $found) {
                        throw new \InvalidArgumentException("Index column '{$keyIndex->Column_name}' not found in model '" . class_basename($model) . "'");
                    }

                    $keyCount++;
                    $queryDefinition .= $this->getColumnGraphTypeDefinition($found) . ' @where(operator: "=")' . ($keyCount < $count ? ', ' : '');
                });

                $querySchema .= $queryDefinition . "): {$modelBaseName} @find" . PHP_EOL;
            });

        $fields = [];
        foreach ($modelTableColumns as $column) {
            $fields[] = $column->Field;
        }

        $queries[] = "search{$modelBaseName} (searchBy: _ @whereConditions(columns: [\""
            . implode('", "', $fields)
            . "\"])): [{$modelBaseName}] @all";

        $querySchema .= PHP_EOL . implode(PHP_EOL . '    ', $queries) . PHP_EOL . '}';

        $graphql_file_name = Str::snake($modelBaseName) . '_queries.graphql';
        $this->persistQuerySchema($querySchema, $graphql_file_name);
    }

    public function persistQuerySchema(string $querySchema, string $fileName)
    {
        $this->indentedInfo("Persisting query schema to '$fileName'", 2);

        $file = $this->getGraphQLDirectory('queries') . '/' . $fileName;

        $stored = File::put($file, $querySchema);

        if (false === $stored) {
            $this->indentedInfo('Failed to write query schema to file', 2);
        } else {
            $this->indentedInfo('File saved to ' . $file, 2);
        }
    }

    public function getModelColumns(Model $model) : array
    {
        return DB::select('DESCRIBE ' . $model->getTable());
    }

    public function getTableColumnsAsSchemaColumns(Model $model) : array
    {
        $columns = [];

        foreach ($this->getModelColumns($model) as $column) {
            $columns[] = $this->getColumnGraphTypeDefinition($column);
        }

        return array_merge($columns, $this->getRelationships($model));
    }

    public function getColumnGraphTypeDefinition(\stdClass $column) : string
    {
        $type = $this->getColumnGraphType($column);
        $nullable = '!';

        if ($column->Field !== 'id') {
            $nullable = $column->Null == 'NO' ? '!' : '';
        }

        return $column->Field . ': ' . $type . $nullable;
    }

    public function getColumnGraphType(\stdClass $column)
    {
        if ($column->Field == 'id') {
            return 'ID';
        }

        $columnType = strtolower(explode('(', $column->Type)[0]);

        return $this->getCustomTypeMappings()[$columnType] ??
            match ($columnType) {
                'int' => 'Int',
                'tinyint', 'boolean', 'binary' => 'Boolean',
                'float', 'double', 'decimal' => 'Float',
                default => 'String',
            };
    }

    public function getFilterType(string $graphqlType) : string
    {
        return $graphqlType . 'FilterInput';
    }

    public function getRelationships(Model $model) : array
    {
        $relationships = [];

        foreach ((new ReflectionClass($model))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (
                $method->class != get_class($model)
                || ! empty($method->getParameters())
                || $method->getName() == __FUNCTION__
                || empty($method->getReturnType())
                || ! str_contains(
                    $method->getReturnType(),
                    'Illuminate\Database\Eloquent\Relations',
                )
            ) {
                continue;
            }

            try {
                $return = $method->invoke($model);

                if ($return instanceof Relation) {
                    $relationshipType = (new ReflectionClass($return))->getShortName();
                    $relationshipModel = (new ReflectionClass($return->getRelated()))->getName();
                    $relationshipInstance = app($relationshipModel);
                    $type = class_basename($relationshipInstance);

                    if (! str_ends_with($relationshipModel, 'AuditLog')) {
                        if (str_ends_with($relationshipType, 'Many')) {
                            $type = '[' . $type . ']';
                        }

                        $relation = match (strtolower($relationshipType)) {
                            'hasone', 'hasmany', 'belongsto', 'belongstomany', 'hasmanythrough' => ' @' . Str::camel($relationshipType),
                            default => '',
                        };

                        $nullable = '';

                        if (str_starts_with($relationshipType, 'Belongs')) {
                            $nullable = '!';
                        }
                        $relationships[] = $method->getName() . ': ' . $type . $nullable . $relation;
                    }
                }
            } catch (\ErrorException $e) {
            }
        }

        return $relationships;
    }

    public function persistSchema(string $schema) : void
    {
        $this->info('Writing schema to file...');

        $file = $this->getGraphQLDirectory() . '/schema.graphql';

        $customerScalarTypes = implode(PHP_EOL, config('laravel-graphql-schema-generator.custom_scalar_definitions', []));

        $queryImport = '';
        if ($this->option('include-queries')) {
            $queryImport = '#import queries/*.graphql' . PHP_EOL;
        }

        $stored = File::put($file, str_replace(
            [
                '{CUSTOM_SCALAR_DEFINITIONS}',
                '{SCHEMA}',
                '{QUERY_IMPORT}',
            ],
            [
                $customerScalarTypes,
                $schema,
                $queryImport,
            ],
            file_get_contents(config('laravel-graphql-schema-generator.model_stub', app_path('../stubs/graphql_schema.stub'))),
        ));

        if (false === $stored) {
            $this->indentedInfo('Failed to write schema to file');
        } else {
            $this->indentedInfo('File saved to ' . $file);
        }
    }

    public function getGraphQLDirectory(string $subdirectory = '') : string
    {
        $file_path = config('laravel-graphql-schema-generator.schema_path', app_path('../graphql'))
            . ($subdirectory ? '/' . $subdirectory : '');

        if (! file_exists($file_path)) {
            mkdir($file_path, 0755, true);
        }

        return $file_path;
    }

    public function getCustomTypeMappings() : array
    {
        if (! $this->customTypeMappings) {
            $this->customTypeMappings = array_change_key_case(
                config('laravel-graphql-schema-generator.custom_type_mappings', []),
                CASE_LOWER,
            );
        }

        return $this->customTypeMappings;
    }
}
