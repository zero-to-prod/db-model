<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use ZeroToProd\DbModel\ColumnDefinition;
use ZeroToProd\DbModel\ColumnType;
use ZeroToProd\DbModel\HasColumnAttribute;
use ZeroToProd\DbModel\Schema;
use ZeroToProd\DbModel\SourceSchema;
use ZeroToProd\DbModel\Table;
use ZeroToProd\DbModel\TableDefinition;
use ZeroToProd\DbModel\TableRenderer;
use ZeroToProd\DbModel\Tests\Fixtures\Db\Testing\Testing;
use ZeroToProd\DbModel\Tests\Fixtures\Db\Testing\Widgets;
use ZeroToProd\DbModel\Tests\Fixtures\HasFixtureColumn;

test('the schema enum declares its name and collation', function (): void {
    $Schema = new ReflectionClass(Testing::class)->getAttributes(Schema::class)[0]->newInstance();

    expect($Schema->attributes)->toBe([
        Schema::name => 'testing',
        Schema::collate => 'utf8mb4_0900_ai_ci',
    ]);
});

test('a table enum declares its schema, name and collation', function (): void {
    $Table = new ReflectionClass(Widgets::class)->getAttributes(Table::class)[0]->newInstance();

    expect($Table->schema)->toBe(Testing::class)
        ->and($Table->attributes)->toBe([
            Table::name => 'widgets',
            Table::collate => 'utf8mb4_unicode_ci',
        ])
        ->and($Table->attributes)->not->toHaveKey(Table::indexes);
});

test('the source schema resolves its enum, namespace and directory', function (): void {
    $SourceSchema = SourceSchema::make('Testing');

    expect($SourceSchema->schema)->toBe(Testing::class)
        ->and($SourceSchema->namespace)->toBe('ZeroToProd\DbModel\Tests\Fixtures\Db\Testing')
        ->and($SourceSchema->directory)->toBe(dirname(__DIR__).'/Fixtures/Db/Testing')
        ->and($SourceSchema->trait)->toBe(HasColumnAttribute::class)
        ->and($SourceSchema->className('personal_access_tokens'))->toBe('PersonalAccessTokens')
        ->and($SourceSchema->path('widgets'))->toBe(dirname(__DIR__).'/Fixtures/Db/Testing/Widgets.php');
});

test('the source schema rejects a configured trait that is not one', function (): void {
    $this->withConfig(['db-model.trait' => Testing::class]);

    expect(static fn (): SourceSchema => SourceSchema::make('Testing'))
        ->toThrow(RuntimeException::class, 'The configured [db-model.trait] is not a trait');
});

test('the renderer uses the configured trait', function (): void {
    $this->withConfig(['db-model.trait' => HasFixtureColumn::class]);

    $rendered = new TableRenderer(SourceSchema::make('Testing'))->render(
        new TableDefinition('sprockets', 'utf8mb4_unicode_ci'),
    );

    expect($rendered)->toContain('use '.HasFixtureColumn::class.';')
        ->toContain('    use HasFixtureColumn;')
        ->not->toContain('HasColumnAttribute');
});

test('the source schema reads every declared table and skips the schema enum', function (): void {
    $tables = SourceSchema::make('Testing')->tables();

    expect(array_keys($tables))->toBe(['gadgets', 'widgets'])
        ->and($tables['gadgets']->indexes)->toBe([
            'gadgets_family_variant_index' => ['family', 'variant'],
        ])
        ->and($tables['gadgets']->columns['code']->unique)->toBeTrue()
        ->and($tables['widgets']->collate)->toBe('utf8mb4_unicode_ci');
});

test('the source schema rejects a directory without a schema enum', function (): void {
    expect(static fn (): SourceSchema => SourceSchema::make('Missing'))
        ->toThrow(RuntimeException::class, 'No enum carrying the #[Schema] attribute was found');
});

test('the renderer reproduces a committed table enum byte for byte', function (): void {
    $SourceSchema = SourceSchema::make('Testing');
    $TableRenderer = new TableRenderer($SourceSchema);

    foreach ($SourceSchema->tables() as $TableDefinition) {
        expect($TableRenderer->render($TableDefinition))->toBe(File::get($SourceSchema->path($TableDefinition->name)));
    }
});

test('the renderer writes a comment and omits an empty index list', function (): void {
    $rendered = new TableRenderer(SourceSchema::make('Testing'))->render(
        new TableDefinition('sprockets', 'utf8mb4_unicode_ci', [
            'label' => new ColumnDefinition(
                name: 'label',
                type: ColumnType::varchar->value,
                length: 32,
                comment: "the sprocket's label",
                nullable: true,
            ),
        ]),
    );

    expect($rendered)->toContain("Column::comment => 'the sprocket\\'s label',")
        ->and($rendered)->toContain('enum Sprockets: string')
        ->and($rendered)->not->toContain('Table::indexes');
});

// A collation is written through as a string, so one the package has never
// heard of round-trips instead of failing the generator.
test('the renderer writes any collation through', function (): void {
    $rendered = new TableRenderer(SourceSchema::make('Testing'))
        ->render(new TableDefinition('sprockets', 'latin1_swedish_ci'));

    expect($rendered)->toContain("Table::collate => 'latin1_swedish_ci',");
});

test('the renderer rejects a column type it cannot name', function (): void {
    $TableRenderer = new TableRenderer(SourceSchema::make('Testing'));
    $TableDefinition = new TableDefinition('sprockets', 'utf8mb4_unicode_ci', [
        'shape' => new ColumnDefinition(name: 'shape', type: 'geometry'),
    ]);

    expect(static fn (): string => $TableRenderer->render($TableDefinition))
        ->toThrow(RuntimeException::class, 'Unsupported column type [geometry]');
});
