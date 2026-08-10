<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use ZeroToProd\DbModel\Column;
use ZeroToProd\DbModel\ColumnType;
use ZeroToProd\DbModel\DatabaseSchema;

beforeEach(function (): void {
    $this->defineFixtureTables();
});

test('the database schema reads a table column by column', function (): void {
    $Widgets = DatabaseSchema::read()['widgets'];

    expect($Widgets->collate)->toBe('utf8mb4_unicode_ci')
        ->and($Widgets->indexes)->toBeEmpty()
        ->and(array_keys($Widgets->columns))->toBe([
            'id', 'label', 'notes', 'summary', 'body', 'active', 'count', 'total', 'created_at',
        ])
        ->and($Widgets->columns['id']->toArray())->toBe([
            Column::type => ColumnType::char->value,
            Column::length => 26,
            Column::comment => 'The unique identifier of the widget',
            Column::primary_key => true,
        ])
        ->and($Widgets->columns['label']->toArray())->toBe([
            Column::type => ColumnType::varchar->value,
            Column::length => 32,
            Column::comment => "The widget's label",
        ])
        // A column without a comment reads as null rather than an empty string.
        ->and($Widgets->columns['notes']->toArray())->toBe([
            Column::type => ColumnType::text->value,
            Column::nullable => true,
        ])
        // An int carries a display width that is not part of the schema.
        ->and($Widgets->columns['count']->toArray())->toBe([
            Column::type => ColumnType::int->value,
        ]);
});

test('the database schema keeps only the indexes a column cannot carry', function (): void {
    $Gadgets = DatabaseSchema::read()['gadgets'];

    expect($Gadgets->collate)->toBe('utf8mb4_0900_ai_ci')
        ->and($Gadgets->indexes)->toBe([
            'gadgets_family_variant_index' => ['family', 'variant'],
        ])
        ->and($Gadgets->columns['id']->toArray())->toBe([
            Column::type => ColumnType::bigint->value,
            Column::comment => 'The unique identifier of the gadget',
            Column::primary_key => true,
            Column::auto_increment => true,
        ])
        ->and($Gadgets->columns['code']->unique)->toBeTrue();
});

test('the database schema is read in table name order', function (): void {
    expect(array_keys(DatabaseSchema::read()))->toBe(['gadgets', 'widgets']);
});

// A database other than the connection's own is read over the same connection.
test('the database schema reads a named database and connection', function (): void {
    expect(DatabaseSchema::read(Config::string('database.connections.mysql.database'), 'mysql'))
        ->toEqual(DatabaseSchema::read());
});
