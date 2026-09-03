<?php

declare(strict_types=1);

namespace ZeroToProd\DbModel;

use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Schema as SchemaFacade;

/** @internal */
final class DatabaseSchema
{
    /**
     * @param  string|null  $database  Defaults to the connection's own database
     * @param  string|null  $connection  Defaults to the application's default connection
     * @return array<string, TableDefinition>
     */
    public static function read(?string $database = null, ?string $connection = null): array
    {
        $Builder = SchemaFacade::connection($connection);
        $tables = [];

        foreach ($Builder->getTables($database ?? $Builder->getConnection()->getDatabaseName()) as $table) {
            $tables[$table['name']] = self::table(
                $Builder,
                $table['schema_qualified_name'],
                $table['name'],
                $table['collation'] ?? '',
                $table['comment'] ?? '',
            );
        }

        ksort($tables);

        return $tables;
    }

    /** A driver reporting no table comment — SQLite and SQL Server never do — reads as none at all. */
    private static function table(Builder $Builder, string $reference, string $name, string $collate, string $comment): TableDefinition
    {
        $primary = [];
        $unique = [];
        $indexes = [];

        foreach ($Builder->getIndexes($reference) as $index) {
            if ($index['primary']) {
                $primary = $index['columns'];
            } elseif ($index['unique'] && count($index['columns']) === 1) {
                $unique[] = $index['columns'][0];
            } else {
                $indexes[$index['name']] = $index['columns'];
            }
        }

        ksort($indexes);

        $columns = [];

        foreach ($Builder->getColumns($reference) as $column) {
            $columns[$column['name']] = new ColumnDefinition(
                name: $column['name'],
                type: $column['type_name'],
                length: self::length($column['type_name'], $column['type']),
                comment: ($column['comment'] ?? '') === '' ? null : $column['comment'],
                nullable: $column['nullable'],
                unique: in_array($column['name'], $unique, true),
                primary_key: in_array($column['name'], $primary, true),
                auto_increment: $column['auto_increment'],
            );
        }

        return new TableDefinition($name, $collate, $columns, $indexes, $comment === '' ? null : $comment);
    }

    private static function length(string $type_name, string $type): ?int
    {
        return in_array($type_name, [ColumnType::varchar->value, ColumnType::char->value], true)
            && preg_match('/\((\d+)\)/', $type, $matches) === 1
                ? (int) $matches[1]
                : null;
    }
}
