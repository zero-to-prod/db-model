<?php

declare(strict_types=1);

namespace ZeroToProd\DbModel\Tests\Fixtures\PublicApi;

enum Status: string
{
    case Draft = 'draft';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
