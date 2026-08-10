<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZeroToProd\DbModel\Internal\Installer;
use ZeroToProd\DbModel\Internal\Mcp\Server;
use ZeroToProd\DbModel\Internal\Mcp\Tools\Install;
use ZeroToProd\DbModel\Tests\Fixtures\HasFixtureColumn;

beforeEach(function (): void {
    $this->defineFixtureTables();

    File::delete(Installer::path());
    File::deleteDirectory(toolDirectory());
});

afterEach(function (): void {
    File::delete(Installer::path());
    File::deleteDirectory(toolDirectory());
});

function toolDatabase(): string
{
    $database = env('DB_DATABASE', 'testing_db_model');

    return is_string($database) ? $database : 'testing_db_model';
}

/** The fixture tree stands in for an application's App\Sources\Db. */
function toolDirectory(): string
{
    return dirname(__DIR__).'/Fixtures/Db/'.Str::studly(toolDatabase());
}

/** @return array<string, mixed> */
function toolArguments(array $overrides = []): array
{
    return [
        'namespace' => 'ZeroToProd\\DbModel\\Tests\\Fixtures\\Db',
        'path' => dirname(__DIR__).'/Fixtures/Db',
        'trait' => HasFixtureColumn::class,
        'databases' => [toolDatabase()],
        ...$overrides,
    ];
}

it('describes the tool so an agent knows when to call it', function (): void {
    $tool = new Install;

    expect($tool->name())->toBe('install')
        ->and($tool->description())->toContain('db-model:install')
        ->and($tool->toArray()['inputSchema']['properties'])->toHaveKeys([
            'namespace', 'path', 'trait', 'mcp_enabled', 'mcp_handle', 'connection', 'databases', 'generate', 'overwrite',
        ]);
});

it('writes the configuration, the schema enum and the table enums', function (): void {
    Server::tool(Install::class, toolArguments(['mcp_handle' => 'package-docs']))
        ->assertOk()
        ->assertHasNoErrors()
        ->assertSee('created')
        ->assertSee('Widgets');

    expect(File::get(Installer::path()))
        ->toContain("'namespace' => 'ZeroToProd\\\\DbModel\\\\Tests\\\\Fixtures\\\\Db',")
        ->toContain("'path' => '".dirname(__DIR__)."/Fixtures/Db',")
        ->toContain("'trait' => HasFixtureColumn::class,")
        ->toContain("'handle' => 'package-docs',")
        ->and(File::get(toolDirectory().'/'.Str::studly(toolDatabase()).'.php'))
        ->toContain("Schema::name => '".toolDatabase()."',")
        ->and(File::get(toolDirectory().'/Widgets.php'))->toContain('enum Widgets: string');
});

it('declares the database without generating when asked not to', function (): void {
    Server::tool(Install::class, toolArguments(['generate' => false, 'mcp_enabled' => false]))
        ->assertOk()
        ->assertSee('created');

    expect(File::get(Installer::path()))->toContain("'enabled' => false,")
        // Only the schema enum was written.
        ->and(File::files(toolDirectory()))->toHaveCount(1);
});

it('names the databases there are when given one there is not', function (): void {
    Server::tool(Install::class, toolArguments(['databases' => ['nonexistent_db']]))
        ->assertHasErrors()
        ->assertSee('has no database named [nonexistent_db]')
        ->assertSee(toolDatabase());

    expect(File::exists(Installer::path()))->toBeFalse();
});

it("falls back to the connection's own database and the current configuration", function (): void {
    Server::tool(Install::class, ['generate' => false])->assertOk()->assertSee('created');

    expect(File::get(Installer::path()))
        ->toContain("'namespace' => 'ZeroToProd\\\\DbModel\\\\Tests\\\\Fixtures\\\\Db',")
        ->toContain("'handle' => 'db-model',")
        ->and(File::exists(toolDirectory().'/'.Str::studly(toolDatabase()).'.php'))->toBeTrue();
});

it('keeps a configuration that says something else until told to overwrite it', function (): void {
    $arguments = toolArguments(['generate' => false]);

    Server::tool(Install::class, $arguments)->assertOk()->assertSee('created');

    // Nothing moved, so the second call rewrites nothing.
    Server::tool(Install::class, $arguments)->assertOk()->assertSee('unchanged');

    File::put(Installer::path(), 'stale');

    Server::tool(Install::class, $arguments)->assertOk()->assertSee('kept');

    expect(File::get(Installer::path()))->toBe('stale');

    Server::tool(Install::class, [...$arguments, 'overwrite' => true])->assertOk()->assertSee('updated');

    expect(File::get(Installer::path()))->toContain("'trait' => HasFixtureColumn::class,");
});
