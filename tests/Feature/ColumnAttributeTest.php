<?php

declare(strict_types=1);

use ZeroToProd\DbModel\Column;
use ZeroToProd\DbModel\ColumnType;
use ZeroToProd\DbModel\PhpType;
use ZeroToProd\DbModel\Tests\Fixtures\Db\Testing\Gadgets;
use ZeroToProd\DbModel\Tests\Fixtures\Db\Testing\Widgets;
use ZeroToProd\DbModel\Tests\Fixtures\Sprockets;

test('each column type declares the native php type that carries it', function (): void {
    expect(ColumnType::varchar->php())->toBe(PhpType::string)
        ->and(ColumnType::mediumtext->php())->toBe(PhpType::string)
        ->and(ColumnType::text->php())->toBe(PhpType::string)
        ->and(ColumnType::longtext->php())->toBe(PhpType::string)
        ->and(ColumnType::char->php())->toBe(PhpType::string)
        ->and(ColumnType::tinyint->php())->toBe(PhpType::int)
        ->and(ColumnType::int->php())->toBe(PhpType::int)
        ->and(ColumnType::bigint->php())->toBe(PhpType::int)
        ->and(ColumnType::timestamp->php())->toBe(PhpType::DateTimeInterface);
});

test('each native php type declares a validation rule', function (): void {
    expect(ColumnType::varchar->rule())->toBe('string')
        ->and(ColumnType::mediumtext->rule())->toBe('string')
        ->and(ColumnType::text->rule())->toBe('string')
        ->and(ColumnType::longtext->rule())->toBe('string')
        ->and(ColumnType::char->rule())->toBe('string')
        ->and(ColumnType::tinyint->rule())->toBe('integer')
        ->and(ColumnType::int->rule())->toBe('integer')
        ->and(ColumnType::bigint->rule())->toBe('integer')
        ->and(ColumnType::timestamp->rule())->toBe('date');
});

test('a column becomes a list of validation rules', function (): void {
    expect(Widgets::label->rules())->toBe(['required', 'string', 'max:32']);
});

test('a nullable column is nullable rather than required', function (): void {
    expect(Widgets::created_at->rules())->toBe(['nullable', 'date']);
});

test('a length only bounds a string', function (): void {
    expect(Widgets::id->rules())->toBe(['required', 'string', 'max:26'])
        ->and(Widgets::count->rules())->toBe(['required', 'integer'])
        ->and(Widgets::notes->rules())->toBe(['nullable', 'string']);
});

test('unique is not emitted, it is declared per request', function (): void {
    expect(Gadgets::code->rules())->not->toContain('unique');
});

test('auto increment is not emitted as a rule', function (): void {
    expect(Gadgets::id->rules())->toBe(['required', 'integer']);
});

// A column whose table was created without one carries no comment.
test('an absent column attribute reads as null rather than throwing', function (): void {
    expect(Widgets::notes->comment())->toBeNull()
        ->and(Widgets::notes->attribute('nonexistent'))->toBeNull();
});

test('a column attribute is readable by name', function (): void {
    expect(Gadgets::code->length())->toBe(64)
        ->and(Gadgets::code->unique())->toBeTrue()
        ->and(Gadgets::code->comment())->toBe('The code the gadget is ordered by')
        ->and(Gadgets::id->auto_increment())->toBeTrue()
        ->and(Widgets::id->type())->toBe(ColumnType::char->value)
        ->and(Widgets::id->primary_key())->toBeTrue()
        ->and(Widgets::notes->nullable())->toBeTrue();
});

test('a column attribute is readable by the case it belongs to', function (): void {
    expect(ColumnType::from(Widgets::created_at->type())->php())->toBe(PhpType::DateTimeInterface)
        ->and(ColumnType::from(Widgets::count->type())->php())->toBe(PhpType::int);
});

test('a trait of the applications own composes with this one', function (): void {
    expect(Sprockets::label->type())->toBe(ColumnType::varchar->value)
        ->and(Sprockets::label->length())->toBe(32)
        ->and(Sprockets::label->everyAttribute())->toBe([
            Column::name => Sprockets::label,
            Column::type => ColumnType::varchar->value,
            Column::length => 32,
        ]);
});
