<?php

declare(strict_types=1);

namespace ZeroToProd\DbModel\Tests\Fixtures;

use ReflectionException;
use ZeroToProd\DbModel\HasColumnAttribute;

/** Stands in for the trait an application composes to add its own mappings. */
trait HasFixtureColumn
{
    use HasColumnAttribute;

    /**
     * @return array<string, mixed>
     *
     * @throws ReflectionException
     */
    public function everyAttribute(): array
    {
        return $this->arguments();
    }
}
