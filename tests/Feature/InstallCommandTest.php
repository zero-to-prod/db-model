<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Testing\PendingCommand;
use ZeroToProd\DbModel\HasColumnAttribute;
use ZeroToProd\DbModel\Tests\Fixtures\HasFixtureColumn;

beforeEach(function (): void {
    $this->defineFixtureTables();

    File::delete(config_path('db-model.php'));
    File::deleteDirectory(installDirectory());
});

afterEach(function (): void {
    File::delete(config_path('db-model.php'));
    File::deleteDirectory(installDirectory());
    File::deleteDirectory(base_path('app/Sources'));
    File::deleteDirectory(base_path('storage/db-model'));
});

function installDatabase(): string
{
    $database = env('DB_DATABASE', 'testing_db_model');

    return is_string($database) ? $database : 'testing_db_model';
}

function installEnum(): string
{
    return Str::studly(installDatabase());
}

/** The fixture tree stands in for an application's App\Sources\Db. */
function installDirectory(): string
{
    return dirname(__DIR__).'/Fixtures/Db/'.installEnum();
}

test('db-model:install writes the configuration, the schema enum and the table enums', function (): void {
    $this->artisan('db-model:install')
        ->expectsQuestion('The namespace the generated artifacts live under', 'ZeroToProd\\DbModel\\Tests\\Fixtures\\Db')
        ->expectsQuestion('The directory they live in', dirname(__DIR__).'/Fixtures/Db')
        ->expectsQuestion('The trait every generated table enum uses', HasFixtureColumn::class)
        ->expectsConfirmation('Register the MCP server that documents the package to coding agents?', 'yes')
        ->expectsQuestion('The handle the MCP server is registered under', 'package-docs')
        ->expectsQuestion('The connection that reaches the databases', 'mysql')
        ->expectsQuestion('Which databases should be mirrored in PHP?', [installDatabase()])
        ->expectsConfirmation('Generate the table enums now?', 'yes')
        ->assertSuccessful()
        ->run();

    expect(File::get(installDirectory().'/'.installEnum().'.php'))
        ->toContain('namespace ZeroToProd\DbModel\Tests\Fixtures\Db\\'.installEnum().';')
        ->toContain("Schema::name => '".installDatabase()."',")
        ->toContain("Schema::collate => 'utf8mb4_0900_ai_ci',")
        ->toContain('enum '.installEnum().': string {}')
        // The generator ran against the database that was selected.
        ->and(File::get(installDirectory().'/Widgets.php'))->toContain('enum Widgets: string')
        ->and(File::get(installDirectory().'/Gadgets.php'))->toContain('enum Gadgets: string');
});

test('db-model:install writes the answers into the published configuration', function (): void {
    $this->artisan('db-model:install')
        ->expectsQuestion('The namespace the generated artifacts live under', 'ZeroToProd\\DbModel\\Tests\\Fixtures\\Db')
        ->expectsQuestion('The directory they live in', dirname(__DIR__).'/Fixtures/Db')
        ->expectsQuestion('The trait every generated table enum uses', HasFixtureColumn::class)
        ->expectsConfirmation('Register the MCP server that documents the package to coding agents?', 'yes')
        ->expectsQuestion('The handle the MCP server is registered under', 'package-docs')
        ->expectsQuestion('The connection that reaches the databases', 'mysql')
        ->expectsQuestion('Which databases should be mirrored in PHP?', [installDatabase()])
        ->expectsConfirmation('Generate the table enums now?', 'no')
        ->assertSuccessful()
        ->run();

    expect(File::get(config_path('db-model.php')))
        ->toContain("'namespace' => 'ZeroToProd\\\\DbModel\\\\Tests\\\\Fixtures\\\\Db',")
        ->toContain("'path' => '".dirname(__DIR__)."/Fixtures/Db',")
        ->toContain('use '.HasFixtureColumn::class.';')
        ->toContain("'trait' => HasFixtureColumn::class,")
        ->toContain("'enabled' => true,")
        ->toContain("'handle' => 'package-docs',")
        // Nothing was generated, so only the schema enum was written.
        ->and(File::files(installDirectory()))->toHaveCount(1);
});

test('db-model:install expresses an app path with app_path() and turns the MCP server off', function (): void {
    $this->artisan('db-model:install')
        ->expectsQuestion('The namespace the generated artifacts live under', 'App\\Sources\\Db')
        ->expectsQuestion('The directory they live in', 'app/Sources/Db')
        ->expectsQuestion('The trait every generated table enum uses', HasColumnAttribute::class)
        ->expectsConfirmation('Register the MCP server that documents the package to coding agents?', 'no')
        ->expectsQuestion('The connection that reaches the databases', 'mysql')
        ->expectsQuestion('Which databases should be mirrored in PHP?', [installDatabase()])
        ->expectsConfirmation('Generate the table enums now?', 'no')
        ->assertSuccessful()
        ->run();

    expect(File::get(config_path('db-model.php')))
        ->toContain("'namespace' => 'App\\\\Sources\\\\Db',")
        ->toContain("'path' => app_path('Sources/Db'),")
        ->toContain('use ZeroToProd\DbModel\HasColumnAttribute;')
        ->toContain("'trait' => HasColumnAttribute::class,")
        ->toContain("'enabled' => false,")
        ->and(File::exists(base_path('app/Sources/Db/'.installEnum().'/'.installEnum().'.php')))->toBeTrue();
});

test('db-model:install reports what it left alone and asks before overwriting the configuration', function (): void {
    $answers = static fn (PendingCommand $command): PendingCommand => $command
        ->expectsQuestion('The namespace the generated artifacts live under', 'App\\Sources\\Db')
        ->expectsQuestion('The directory they live in', 'storage/db-model')
        ->expectsQuestion('The trait every generated table enum uses', HasColumnAttribute::class)
        ->expectsConfirmation('Register the MCP server that documents the package to coding agents?', 'yes')
        ->expectsQuestion('The handle the MCP server is registered under', 'db-model');

    $file = base_path('storage/db-model/'.installEnum().'/'.installEnum().'.php');
    $tail = static fn (PendingCommand $command): PendingCommand => $command
        ->expectsQuestion('The connection that reaches the databases', 'mysql')
        ->expectsQuestion('Which databases should be mirrored in PHP?', [installDatabase()])
        ->expectsConfirmation('Generate the table enums now?', 'no');

    $tail($answers($this->artisan('db-model:install')))
        ->expectsOutputToContain('created')
        ->assertSuccessful()
        ->run();

    expect(File::get(config_path('db-model.php')))->toContain("'path' => base_path('storage/db-model'),");

    // Second run: both artifacts already say what the answers say.
    $tail($answers($this->artisan('db-model:install')))
        ->expectsOutputToContain('unchanged')
        ->assertSuccessful()
        ->run();

    File::put(config_path('db-model.php'), 'stale');
    File::put($file, 'stale');

    $declined = $answers($this->artisan('db-model:install'))
        ->expectsConfirmation('['.config_path('db-model.php').'] differs from these answers. Overwrite it?', 'no');

    $tail($declined)->expectsOutputToContain('kept')->assertSuccessful()->run();

    expect(File::get(config_path('db-model.php')))->toBe('stale')
        // The schema enum is rewritten either way: the database declares it.
        ->and(File::get($file))->toContain('Schema::name');

    File::put(config_path('db-model.php'), 'stale');
    File::put($file, 'stale');

    $accepted = $answers($this->artisan('db-model:install'))
        ->expectsConfirmation('['.config_path('db-model.php').'] differs from these answers. Overwrite it?', 'yes');

    $tail($accepted)->expectsOutputToContain('updated')->assertSuccessful()->run();

    expect(File::get(config_path('db-model.php')))->toContain("'namespace' => 'App\\\\Sources\\\\Db',")
        ->and(File::get($file))->toContain('Schema::name');
});
