<?php

declare(strict_types=1);

namespace ZeroToProd\DbModel\Internal\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ZeroToProd\DbModel\DatabaseSchema;
use ZeroToProd\DbModel\SourceSchema;
use ZeroToProd\DbModel\TableRenderer;

/** @internal */
class GenerateCommand extends Command
{
    /** @var string */
    protected $signature = 'db-model:generate {--schema=App} {--connection=} {--database=} {--path=} {--dry-run}';

    /** @var string */
    protected $description = 'Scaffold the PHP table enums from the database schema';

    public function handle(): int
    {
        $schema = $this->option('schema');
        $option = $this->option('path');
        $connection = $this->option('connection');
        $database = $this->option('database');
        $SourceSchema = SourceSchema::make(is_string($schema) ? $schema : '');
        $TableRenderer = new TableRenderer($SourceSchema);
        $path = is_string($option) && $option !== '' ? $option : $SourceSchema->directory;
        $dry_run = $this->option('dry-run') === true;

        File::ensureDirectoryExists($path);

        $tables = DatabaseSchema::read(
            is_string($database) && $database !== '' ? $database : null,
            is_string($connection) && $connection !== '' ? $connection : null,
        );

        foreach ($tables as $TableDefinition) {
            $file = $path.'/'.$SourceSchema->className($TableDefinition->name).'.php';
            $contents = $TableRenderer->render($TableDefinition);
            $status = match (true) {
                ! File::exists($file) => 'created',
                File::get($file) === $contents => 'unchanged',
                default => 'updated',
            };

            if ($status !== 'unchanged' && ! $dry_run) {
                File::put($file, $contents);
            }

            $this->components->twoColumnDetail($SourceSchema->className($TableDefinition->name), $status);
        }

        $this->components->info($dry_run ? 'Nothing was written.' : 'The PHP table enums were written to ['.$path.'].');

        return self::SUCCESS;
    }
}
