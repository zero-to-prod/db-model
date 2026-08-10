<?php

declare(strict_types=1);

namespace ZeroToProd\DbModel\Internal\Commands;

use Illuminate\Console\Command;
use ZeroToProd\DbModel\DatabaseSchema;
use ZeroToProd\DbModel\SchemaDiff;
use ZeroToProd\DbModel\SourceSchema;

/** @internal */
class CheckCommand extends Command
{
    /** @var string */
    protected $signature = 'db-model:check {--schema=App} {--connection=} {--database=}';

    /** @var string */
    protected $description = 'Validate the PHP table enums against the database schema';

    public function handle(): int
    {
        $schema = $this->option('schema');
        $connection = $this->option('connection');
        $database = $this->option('database');
        $SourceSchema = SourceSchema::make(is_string($schema) ? $schema : '');
        $tables = DatabaseSchema::read(
            is_string($database) && $database !== '' ? $database : null,
            is_string($connection) && $connection !== '' ? $connection : null,
        );
        $differences = new SchemaDiff($tables, $SourceSchema->tables())->differences();

        $this->output->writeln($differences);
        $this->components->info(count($differences).' difference(s) found. The database is the source of truth: run [db-model:generate] to rebuild the PHP table enums.');

        return $differences === [] ? self::SUCCESS : self::FAILURE;
    }
}
