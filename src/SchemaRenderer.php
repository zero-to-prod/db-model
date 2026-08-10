<?php

declare(strict_types=1);

namespace ZeroToProd\DbModel;

/** @internal */
final readonly class SchemaRenderer
{
    /**
     * @param  string  $namespace  The namespace the table enums share
     * @param  string  $enum  The name of the enum, and of the directory holding it
     */
    public static function render(string $namespace, string $enum, string $database, string $collate): string
    {
        return implode("\n", [
            '<?php',
            '',
            'declare(strict_types=1);',
            '',
            'namespace '.$namespace.';',
            '',
            'use '.Schema::class.';',
            '',
            '#[Schema([',
            '    Schema::name => '.var_export($database, true).',',
            '    Schema::collate => '.var_export($collate, true).',',
            '])]',
            'enum '.$enum.': string {}',
            '',
        ]);
    }
}
