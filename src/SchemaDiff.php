<?php

declare(strict_types=1);

namespace ZeroToProd\DbModel;

/** @internal */
final readonly class SchemaDiff
{
    /**
     * @param  array<string, TableDefinition>  $database
     * @param  array<string, TableDefinition>  $source
     */
    public function __construct(private array $database, private array $source) {}

    /** @return list<string> */
    public function differences(): array
    {
        $differences = [];

        foreach (array_keys($this->database) as $table) {
            if (! isset($this->source[$table])) {
                $differences[] = "Table [{$table}] is not declared in PHP.";

                continue;
            }

            $differences = [...$differences, ...$this->table($table)];
        }

        foreach (array_keys($this->source) as $table) {
            if (! isset($this->database[$table])) {
                $differences[] = "Table [{$table}] is declared in PHP but does not exist in the database.";
            }
        }

        return $differences;
    }

    /** @return list<string> */
    private function table(string $table): array
    {
        $Database = $this->database[$table];
        $Source = $this->source[$table];
        $differences = [];

        if ($Database->collate !== $Source->collate) {
            $differences[] = "Table [{$table}] declares collate [{$Source->collate}], expected [{$Database->collate}].";
        }

        if ($Database->indexes !== $Source->indexes) {
            $differences[] = "Table [{$table}] declares indexes ".$this->encode($Source->indexes).', expected '.$this->encode($Database->indexes).'.';
        }

        foreach ($Database->columns as $column => $ColumnDefinition) {
            if (! isset($Source->columns[$column])) {
                $differences[] = "Column [{$table}.{$column}] is not declared in PHP.";

                continue;
            }

            $expected = $ColumnDefinition->toArray();
            $declared = $Source->columns[$column]->toArray();

            if ($expected !== $declared) {
                $differences[] = "Column [{$table}.{$column}] declares ".$this->encode($declared).', expected '.$this->encode($expected).'.';
            }
        }

        foreach (array_keys($Source->columns) as $column) {
            if (! isset($Database->columns[$column])) {
                $differences[] = "Column [{$table}.{$column}] is declared in PHP but does not exist in the database.";
            }
        }

        $expected = array_keys($Database->columns);
        $declared = array_keys($Source->columns);

        if ($expected !== $declared && array_diff($expected, $declared) === [] && array_diff($declared, $expected) === []) {
            $differences[] = "Table [{$table}] declares columns in the order ".$this->encode($declared).', expected '.$this->encode($expected).'.';
        }

        return $differences;
    }

    private function encode(mixed $value): string
    {
        return (string) json_encode($value);
    }
}
