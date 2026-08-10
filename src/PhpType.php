<?php

declare(strict_types=1);

namespace ZeroToProd\DbModel;

use Attribute;

/**
 * Maps a column type onto the native PHP type that carries it. Match on these
 * constants to map a column onto anything else.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
readonly class PhpType
{
    public const string string = 'string';

    public const string int = 'int';

    public const string DateTimeInterface = 'DateTimeInterface';

    public function __construct(public string $type) {}
}
