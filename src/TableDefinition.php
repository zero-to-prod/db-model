<?php

declare(strict_types=1);

namespace ZeroToProd\DbModel;

use BackedEnum;

/** @internal */
final readonly class TableDefinition
{
    /**
     * @param  array<string, ColumnDefinition>  $columns
     * @param  array<string, list<string>>  $indexes
     * @param  string|null  $comment  The table's own comment, null when it carries none
     */
    public function __construct(
        public string $name,
        public string $collate,
        public array $columns = [],
        public array $indexes = [],
        public ?string $comment = null,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, ColumnDefinition>  $columns
     */
    public static function fromAttributes(array $attributes, array $columns): self
    {
        $name = $attributes[Table::name] ?? null;
        $collate = $attributes[Table::collate] ?? null;
        $comment = $attributes[Table::comment] ?? null;
        $declared = $attributes[Table::indexes] ?? [];
        $indexes = [];

        foreach (is_array($declared) ? $declared : [] as $index => $cases) {
            $indexes[(string) $index] = array_values(array_map(
                static fn (mixed $case): string => $case instanceof BackedEnum ? (string) $case->value : '',
                is_array($cases) ? $cases : [],
            ));
        }

        ksort($indexes);

        return new self(
            is_string($name) ? $name : '',
            is_string($collate) ? $collate : '',
            $columns,
            $indexes,
            is_string($comment) && $comment !== '' ? $comment : null,
        );
    }
}
