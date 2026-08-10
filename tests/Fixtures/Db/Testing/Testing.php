<?php

declare(strict_types=1);

namespace ZeroToProd\DbModel\Tests\Fixtures\Db\Testing;

use ZeroToProd\DbModel\Schema;

#[Schema([
    Schema::name => 'testing',
    Schema::collate => 'utf8mb4_0900_ai_ci',
])]
enum Testing: string {}
