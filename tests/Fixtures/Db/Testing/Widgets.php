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
        Table::name => 'widgets',
        Table::collate => 'utf8mb4_unicode_ci',
    ])]
enum Widgets: string
{
    use HasColumnAttribute;

    #[Column([
        Column::name => self::id,
        Column::comment => 'The unique identifier of the widget',
        Column::type => ColumnType::char->value,
        Column::length => 26,
        Column::nullable => false,
        Column::primary_key => true,
    ])]
    case id = 'id';

    #[Column([
        Column::name => self::label,
        Column::comment => 'The widget\'s label',
        Column::type => ColumnType::varchar->value,
        Column::length => 32,
        Column::nullable => false,
    ])]
    case label = 'label';

    #[Column([
        Column::name => self::notes,
        Column::type => ColumnType::text->value,
        Column::nullable => true,
    ])]
    case notes = 'notes';

    #[Column([
        Column::name => self::summary,
        Column::type => ColumnType::mediumtext->value,
        Column::nullable => false,
    ])]
    case summary = 'summary';

    #[Column([
        Column::name => self::body,
        Column::type => ColumnType::longtext->value,
        Column::nullable => false,
    ])]
    case body = 'body';

    #[Column([
        Column::name => self::active,
        Column::type => ColumnType::tinyint->value,
        Column::nullable => false,
    ])]
    case active = 'active';

    #[Column([
        Column::name => self::count,
        Column::type => ColumnType::int->value,
        Column::nullable => false,
    ])]
    case count = 'count';

    #[Column([
        Column::name => self::total,
        Column::type => ColumnType::bigint->value,
        Column::nullable => false,
    ])]
    case total = 'total';

    #[Column([
        Column::name => self::created_at,
        Column::type => ColumnType::timestamp->value,
        Column::nullable => true,
    ])]
    case created_at = 'created_at';
}
