<?php

declare(strict_types=1);

namespace ZeroToProd\DbModel;

use RuntimeException;

/** @internal */
final readonly class TableRenderer
{
    public function __construct(private SourceSchema $SourceSchema) {}

    public function render(TableDefinition $TableDefinition): string
    {
        $lines = [
            '<?php',
            '',
            'declare(strict_types=1);',
            '',
            'namespace '.$this->SourceSchema->namespace.';',
            '',
            ...$this->imports(),
            '',
            '/**',
            ' * @method string type()',
            ' * @method string|null comment()',
            ' * @method int|null length()',
            ' * @method bool|null nullable()',
            ' * @method bool|null unique()',
            ' * @method bool|null primary_key()',
            ' * @method bool|null auto_increment()',
            ' */',
            '#[Table(',
            '    schema: '.class_basename($this->SourceSchema->schema).'::class,',
            '    attributes: [',
            '        Table::name => '.var_export($TableDefinition->name, true).',',
            '        Table::collate => '.var_export($TableDefinition->collate, true).',',
            ...$this->indexes($TableDefinition),
            '    ])]',
            'enum '.$this->SourceSchema->className($TableDefinition->name).': string'.$this->implemented(),
            '{',
            '    use '.class_basename($this->SourceSchema->trait).';',
        ];

        foreach ($TableDefinition->columns as $ColumnDefinition) {
            $lines = [...$lines, '', ...$this->column($ColumnDefinition)];
        }

        return implode("\n", [...$lines, '}', '']);
    }

    /** The `implements` clause the enum declaration carries, empty when none is configured. */
    private function implemented(): string
    {
        return $this->SourceSchema->implements === []
            ? ''
            : ' implements '.implode(', ', array_map(class_basename(...), $this->SourceSchema->implements));
    }

    /** @return list<string> */
    private function imports(): array
    {
        $imports = [
            Column::class,
            ColumnType::class,
            $this->SourceSchema->trait,
            Table::class,
            ...$this->SourceSchema->implements,
        ];

        sort($imports);

        return array_map(static fn (string $import): string => 'use '.$import.';', $imports);
    }

    /** @return list<string> */
    private function indexes(TableDefinition $TableDefinition): array
    {
        if ($TableDefinition->indexes === []) {
            return [];
        }

        $lines = ['        Table::indexes => ['];

        foreach ($TableDefinition->indexes as $index => $columns) {
            $lines[] = '            '.var_export($index, true).' => [';

            foreach ($columns as $column) {
                $lines[] = "                self::{$column},";
            }

            $lines[] = '            ],';
        }

        return [...$lines, '        ],'];
    }

    /** @return list<string> */
    private function column(ColumnDefinition $ColumnDefinition): array
    {
        $ColumnType = ColumnType::tryFrom($ColumnDefinition->type)
            ?? throw new RuntimeException("Unsupported column type [{$ColumnDefinition->type}]. Add it to ".ColumnType::class.'.');

        $lines = ['    #[Column([', "        Column::name => self::{$ColumnDefinition->name},"];

        if ($ColumnDefinition->comment !== null) {
            $lines[] = '        Column::comment => '.var_export($ColumnDefinition->comment, true).',';
        }

        $lines[] = '        Column::type => ColumnType::'.$ColumnType->name.'->value,';

        if ($ColumnDefinition->length !== null) {
            $lines[] = "        Column::length => {$ColumnDefinition->length},";
        }

        $lines[] = '        Column::nullable => '.var_export($ColumnDefinition->nullable, true).',';

        if ($ColumnDefinition->unique) {
            $lines[] = '        Column::unique => true,';
        }

        if ($ColumnDefinition->primary_key) {
            $lines[] = '        Column::primary_key => true,';
        }

        if ($ColumnDefinition->auto_increment) {
            $lines[] = '        Column::auto_increment => true,';
        }

        return [...$lines, '    ])]', "    case {$ColumnDefinition->name} = ".var_export($ColumnDefinition->name, true).';'];
    }
}
