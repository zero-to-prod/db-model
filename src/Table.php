<?php

declare(strict_types=1);

namespace ZeroToProd\DbModel;

use Attribute;

/** Declares the table a table enum mirrors. */
#[Attribute(Attribute::TARGET_CLASS)]
class Table
{
    public const string name = 'name';

    public const string collate = 'collate';

    public const string indexes = 'indexes';

    /** @param  array<string, mixed>  $attributes */
    public function __construct(public string $schema, public array $attributes = []) {}
}
