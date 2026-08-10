<?php

declare(strict_types=1);

namespace ZeroToProd\DbModel;

use Attribute;

/** Declares the database a directory of table enums mirrors. */
#[Attribute(Attribute::TARGET_CLASS)]
class Schema
{
    public const string name = 'name';

    public const string collate = 'collate';

    /** @param  array<string, mixed>  $attributes */
    public function __construct(public array $attributes = []) {}
}
