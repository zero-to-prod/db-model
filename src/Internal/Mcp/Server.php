<?php

declare(strict_types=1);

namespace ZeroToProd\DbModel\Internal\Mcp;

use Laravel\Mcp\Server\Tool;
use ZeroToProd\DbModel\Internal\Mcp\Tools\Api;
use ZeroToProd\DbModel\Internal\Mcp\Tools\Readme;

/** @internal */
class Server extends \Laravel\Mcp\Server
{
    protected string $name = 'Db Model';

    protected string $version = '1.0.0';

    protected string $instructions = <<<'MARKDOWN'
        Documents this package for coding agents.

        - `readme` — installation, configuration, usage, limitations.
        - `api` — exact signatures. Anything unlisted is internal: do not call it.
        MARKDOWN;

    /** @var array<int, class-string<Tool>> */
    protected array $tools = [
        Readme::class,
        Api::class,
    ];
}
