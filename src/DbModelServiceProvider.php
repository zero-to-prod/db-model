<?php

declare(strict_types=1);

namespace ZeroToProd\DbModel;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Laravel\Mcp\Facades\Mcp;
use Override;
use ZeroToProd\DbModel\Internal\Mcp\Server;

/** @internal */
class DbModelServiceProvider extends ServiceProvider
{
    /** @internal */
    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/db-model.php', 'db-model');
    }

    /** @internal */
    public function boot(): void
    {
        $this->registerMcpServer();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/db-model.php' => config_path('db-model.php'),
            ], 'db-model-config');
        }
    }

    private function registerMcpServer(): void
    {
        // @codeCoverageIgnoreStart
        if (! class_exists(Mcp::class)) {
            return;
        }
        // @codeCoverageIgnoreEnd

        if (! Config::boolean('db-model.mcp.enabled', true)) {
            return;
        }

        Mcp::local(Config::string('db-model.mcp.handle', 'db-model'), Server::class);
    }
}
