<?php

declare(strict_types=1);

namespace ZeroToProd\DbModel\Tests\Fixtures\Db\Testing;

use ZeroToProd\DbModel\Column;
use ZeroToProd\DbModel\ColumnType;
use ZeroToProd\DbModel\HasColumnAttribute;
use ZeroToProd\DbModel\Table;

/**
 * @method string type()
 * @method string|null comment()
 * @method int|null length()
 * @method bool|null nullable()
 * @method bool|null unique()
 * @method bool|null primary_key()
 * @method bool|null auto_increment()
 */
#[Table(
    schema: Testing::class,
    attributes: [
        Table::name => 'gadgets',
        Table::collate => 'utf8mb4_0900_ai_ci',
        Table::indexes => [
            'gadgets_family_variant_index' => [
                self::family,
                self::variant,
            ],
        ],
    ])]
enum Gadgets: string
{
    use HasColumnAttribute;

    #[Column([
        Column::name => self::id,
        Column::comment => 'The unique identifier of the gadget',
        Column::type => ColumnType::bigint->value,
        Column::nullable => false,
        Column::primary_key => true,
        Column::auto_increment => true,
    ])]
    case id = 'id';

    #[Column([
        Column::name => self::code,
        Column::comment => 'The code the gadget is ordered by',
        Column::type => ColumnType::varchar->value,
        Column::length => 64,
        Column::nullable => false,
        Column::unique => true,
    ])]
    case code = 'code';

    #[Column([
        Column::name => self::family,
        Column::comment => 'The family the gadget belongs to',
        Column::type => ColumnType::varchar->value,
        Column::length => 255,
        Column::nullable => false,
    ])]
    case family = 'family';

    #[Column([
        Column::name => self::variant,
        Column::comment => 'The variant within the family',
        Column::type => ColumnType::varchar->value,
        Column::length => 255,
        Column::nullable => false,
    ])]
    case variant = 'variant';
}
