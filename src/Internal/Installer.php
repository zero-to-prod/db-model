<?php

declare(strict_types=1);

namespace ZeroToProd\DbModel\Internal;

use Closure;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZeroToProd\DbModel\HasColumnAttribute;
use ZeroToProd\DbModel\SchemaRenderer;

/** @internal */
final class Installer
{
    public static function path(): string
    {
        return config_path('db-model.php');
    }

    public static function configuration(string $namespace, string $path, string $trait, bool $mcp, string $handle): string
    {
        return str_replace([
            "'App\\\\Sources\\\\Db'",
            "app_path('Sources/Db')",
            'use '.HasColumnAttribute::class.';',
            'HasColumnAttribute::class',
            "'enabled' => true,",
            "'handle' => 'db-model',",
        ], [
            var_export($namespace, true),
            self::expression($path),
            'use '.$trait.';',
            class_basename($trait).'::class',
            "'enabled' => ".var_export($mcp, true).',',
            "'handle' => ".var_export($handle, true).',',
        ], File::get(dirname(__DIR__, 2).'/config/db-model.php'));
    }

    public static function apply(string $namespace, string $path, string $trait): void
    {
        Config::set('db-model.namespace', $namespace);
        Config::set('db-model.path', self::absolute($path));
        Config::set('db-model.trait', $trait);
    }

    /** @return array{0: string, 1: string} The enum's name and what happened to it */
    public static function schema(string $namespace, string $database, string $collate): array
    {
        $enum = Str::studly($database);
        $directory = Config::string('db-model.path').'/'.$enum;

        File::ensureDirectoryExists($directory);

        $status = self::write(
            $directory.'/'.$enum.'.php',
            SchemaRenderer::render($namespace.'\\'.$enum, $enum, $database, $collate),
            static fn (): bool => true,
        );

        return [$enum, $status];
    }

    /**
     * @param  Closure(): bool  $overwrite  Consulted only when the file on disk says something else
     * @return 'created'|'unchanged'|'updated'|'kept'
     *
     * @throws FileNotFoundException
     */
    public static function write(string $file, string $contents, Closure $overwrite): string
    {
        $status = match (true) {
            ! File::exists($file) => 'created',
            File::get($file) === $contents => 'unchanged',
            $overwrite() => 'updated',
            default => 'kept',
        };

        if ($status === 'created' || $status === 'updated') {
            File::ensureDirectoryExists(dirname($file));
            File::put($file, $contents);
        }

        return $status;
    }

    /**
     * Every database on the connection that is not MySQL's own, mapped to its
     * default collation.
     *
     * @return array<string, string>
     */
    public static function databases(string $connection): array
    {
        $databases = [];

        // Aliased, because MySQL answers information_schema in upper case.
        $rows = DB::connection($connection)->select(
            'select schema_name as name, default_collation_name as collation from information_schema.schemata'
            .' where schema_name not in (?, ?, ?, ?) order by schema_name',
            ['information_schema', 'mysql', 'performance_schema', 'sys'],
        );

        foreach ($rows as $row) {
            $values = array_map(strval(...), (array) $row);
            $databases[$values['name']] = $values['collation'];
        }

        return $databases;
    }

    /** The path as config/db-model.php should express it. */
    public static function expression(string $path): string
    {
        return match (true) {
            str_starts_with($path, 'app/') => 'app_path('.var_export(substr($path, 4), true).')',
            str_starts_with($path, '/') => var_export($path, true),
            default => 'base_path('.var_export($path, true).')',
        };
    }

    public static function relative(string $path): string
    {
        return str_starts_with($path, base_path().'/') ? substr($path, strlen(base_path()) + 1) : $path;
    }

    public static function absolute(string $path): string
    {
        return str_starts_with($path, '/') ? $path : base_path($path);
    }
}
