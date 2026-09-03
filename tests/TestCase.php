<?php

declare(strict_types=1);

namespace ZeroToProd\DbModel\Tests;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Mcp\Server\McpServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use PDO;
use ZeroToProd\DbModel\DbModelServiceProvider;

abstract class TestCase extends Orchestra
{
    /** @var array<string, mixed> */
    protected array $environmentConfig = [];

    private static bool $created = false;

    /** @return array<int, class-string> */
    protected function getPackageProviders($app): array
    {
        return [
            McpServiceProvider::class,
            DbModelServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $config = $app->make(Repository::class);

        $config->set('db-model.namespace', __NAMESPACE__.'\\Fixtures\\Db');
        $config->set('db-model.path', __DIR__.'/Fixtures/Db');

        $config->set('database.default', 'mysql');
        $config->set('database.connections.mysql', [
            'driver' => 'mysql',
            'host' => $this->environment('DB_HOST', '127.0.0.1'),
            'port' => $this->environment('DB_PORT', '3306'),
            'database' => $this->environment('DB_DATABASE', 'testing_db_model'),
            'username' => $this->environment('DB_USERNAME', 'sail'),
            'password' => $this->environment('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_0900_ai_ci',
        ]);

        foreach ($this->environmentConfig as $key => $value) {
            $config->set($key, $value);
        }
    }

    /** @param  array<string, mixed>  $config */
    protected function withConfig(array $config): static
    {
        $this->environmentConfig = $config;

        $this->refreshApplication();

        return $this;
    }

    protected function defineFixtureTables(): void
    {
        $this->createDatabase();

        Schema::dropAllTables();

        Schema::create('gadgets', static function (Blueprint $table): void {
            $table->collation('utf8mb4_0900_ai_ci');
            $table->comment('The gadgets a customer orders');
            $table->id()->comment('The unique identifier of the gadget');
            $table->string('code', 64)->unique()->comment('The code the gadget is ordered by');
            $table->string('family')->comment('The family the gadget belongs to');
            $table->string('variant')->comment('The variant within the family');
            $table->index(['family', 'variant']);
        });

        Schema::create('widgets', static function (Blueprint $table): void {
            $table->collation('utf8mb4_unicode_ci');
            $table->char('id', 26)->primary()->comment('The unique identifier of the widget');
            $table->string('label', 32)->comment("The widget's label");
            $table->text('notes')->nullable();
            $table->mediumText('summary');
            $table->longText('body');
            $table->tinyInteger('active');
            $table->integer('count');
            $table->bigInteger('total');
            $table->timestamp('created_at')->nullable();
        });
    }

    private function createDatabase(): void
    {
        if (self::$created) {
            return;
        }

        $database = $this->environment('DB_DATABASE', 'testing_db_model');

        new PDO(
            sprintf('mysql:host=%s;port=%s', $this->environment('DB_HOST', '127.0.0.1'), $this->environment('DB_PORT', '3306')),
            $this->environment('DB_USERNAME', 'sail'),
            $this->environment('DB_PASSWORD', ''),
        )->exec("create database if not exists `{$database}`");

        self::$created = true;
    }

    private function environment(string $key, string $default): string
    {
        $value = env($key, $default);

        return is_string($value) ? $value : $default;
    }
}
