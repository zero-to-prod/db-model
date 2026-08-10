<?php

declare(strict_types=1);

namespace ZeroToProd\DbModel\Tests\Fixtures;

use ZeroToProd\DbModel\Column;
use ZeroToProd\DbModel\ColumnType;

/**
 * A table enum as an application composing its own trait would have it.
 *
 * @method string type()
 * @method int|null length()
 */
enum Sprockets: string
{
    use HasFixtureColumn;

    #[Column([
        Column::name => self::label,
        Column::type => ColumnType::varchar->value,
        Column::length => 32,
    ])]
    case label = 'label';
}
