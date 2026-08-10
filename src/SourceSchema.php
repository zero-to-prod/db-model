<?php

declare(strict_types=1);

namespace ZeroToProd\DbModel;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use ReflectionClass;
use RuntimeException;

/** @internal */
final readonly class SourceSchema
{
    /**
     * @param  class-string  $schema
     * @param  trait-string  $trait
     */
    private function __construct(
        public string $schema,
        public string $namespace,
        public string $directory,
        public string $trait,
    ) {}

    public static function make(string $name): self
    {
        $namespace = Config::string('db-model.namespace').'\\'.$name;
        $schema = $namespace.'\\'.$name;

        if (! enum_exists($schema) || new ReflectionClass($schema)->getAttributes(Schema::class) === []) {
            throw new RuntimeException("No enum carrying the #[Schema] attribute was found at [{$schema}].");
        }

        $trait = Config::string('db-model.trait', HasColumnAttribute::class);

        if (! trait_exists($trait)) {
            throw new RuntimeException("The configured [db-model.trait] is not a trait: [{$trait}].");
        }

        return new self($schema, $namespace, Config::string('db-model.path').'/'.$name, $trait);
    }

    public function className(string $table): string
    {
        return Str::studly($table);
    }

    public function path(string $table): string
    {
        return $this->directory.'/'.$this->className($table).'.php';
    }

    /** @return array<string, TableDefinition> */
    public function tables(): array
    {
        $tables = [];

        foreach (glob($this->directory.'/*.php') ?: [] as $file) {
            $class = $this->namespace.'\\'.basename($file, '.php');

            if (! class_exists($class) || new ReflectionClass($class)->getAttributes(Table::class) === []) {
                continue;
            }

            $Reflection = new ReflectionClass($class);
            $attributes = $Reflection->getAttributes(Table::class);
            $columns = [];

            foreach ($Reflection->getReflectionConstants() as $Constant) {
                foreach ($Constant->getAttributes(Column::class) as $Attribute) {
                    $ColumnDefinition = ColumnDefinition::fromAttributes($Attribute->newInstance()->attributes);
                    $columns[$ColumnDefinition->name] = $ColumnDefinition;
                }
            }

            $TableDefinition = TableDefinition::fromAttributes($attributes[0]->newInstance()->attributes, $columns);
            $tables[$TableDefinition->name] = $TableDefinition;
        }

        ksort($tables);

        return $tables;
    }
}
