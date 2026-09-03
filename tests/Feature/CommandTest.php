<?php

declare(strict_types=1);

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    $this->defineFixtureTables();
});

test('db-model:check passes while the committed enums mirror the database', function (): void {
    expect(Artisan::call('db-model:check', ['--schema' => 'Testing']))->toBe(Command::SUCCESS)
        ->and(Artisan::output())->toContain('0 difference(s) found');
});

test('db-model:check fails and names the drift', function (): void {
    Schema::create('sprockets', static function (Blueprint $table): void {
        $table->id();
    });

    expect(Artisan::call('db-model:check', ['--schema' => 'Testing']))->toBe(Command::FAILURE)
        ->and(Artisan::output())
        ->toContain('Table [sprockets] is not declared in PHP.')
        ->toContain('1 difference(s) found');
});

test('db-model:check fails when the table comment drifts', function (): void {
    Schema::table('gadgets', static function (Blueprint $table): void {
        $table->comment('Something else entirely');
    });

    expect(Artisan::call('db-model:check', ['--schema' => 'Testing']))->toBe(Command::FAILURE)
        ->and(Artisan::output())
        ->toContain('Table [gadgets] declares comment "The gadgets a customer orders", expected "Something else entirely".')
        ->toContain('1 difference(s) found');
});

test('db-model:generate creates, leaves and rewrites the table enums', function (): void {
    $path = storage_path('framework/testing/db-model');

    File::deleteDirectory($path);

    expect(Artisan::call('db-model:generate', ['--schema' => 'Testing', '--path' => $path]))->toBe(Command::SUCCESS)
        ->and(Artisan::output())->toContain('created')
        ->and(File::get($path.'/Widgets.php'))->toBe(File::get(dirname(__DIR__).'/Fixtures/Db/Testing/Widgets.php'))
        ->and(Artisan::call('db-model:generate', ['--schema' => 'Testing', '--path' => $path]))->toBe(Command::SUCCESS)
        ->and(Artisan::output())->toContain('unchanged');

    File::put($path.'/Widgets.php', 'stale');

    expect(Artisan::call('db-model:generate', ['--schema' => 'Testing', '--path' => $path]))->toBe(Command::SUCCESS)
        ->and(Artisan::output())->toContain('updated')
        ->and(File::get($path.'/Widgets.php'))->not->toBe('stale');

    File::deleteDirectory($path);
});

// Dry run, so a database that disagrees with the committed fixtures reports
// the disagreement rather than quietly rewriting them.
test('db-model:generate falls back to the source schema directory', function (): void {
    expect(Artisan::call('db-model:generate', ['--schema' => 'Testing', '--dry-run' => true]))->toBe(Command::SUCCESS)
        ->and(Artisan::output())->toContain('unchanged');
});

test('db-model:generate writes nothing on a dry run', function (): void {
    $path = storage_path('framework/testing/db-model-dry-run');

    File::deleteDirectory($path);

    expect(Artisan::call('db-model:generate', ['--schema' => 'Testing', '--path' => $path, '--dry-run' => true]))->toBe(Command::SUCCESS)
        ->and(Artisan::output())->toContain('Nothing was written.')
        ->and(File::files($path))->toBeEmpty();

    File::deleteDirectory($path);
});
