<?php

declare(strict_types=1);

namespace ZeroToProd\DbModel\Internal\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use ZeroToProd\DbModel\HasColumnAttribute;
use ZeroToProd\DbModel\Internal\Installer;

/** @internal */
class InstallCommand extends Command
{
    /** @var string */
    protected $signature = 'db-model:install';

    /** @var string */
    protected $description = 'Configure the package and declare the databases to generate table enums for';

    public function handle(): int
    {
        $namespace = trim($this->text('The namespace the generated artifacts live under', Config::string('db-model.namespace')), '\\');
        $path = rtrim($this->text('The directory they live in', Installer::relative(Config::string('db-model.path'))), '/');
        $trait = trim($this->text('The trait every generated table enum uses', Config::string('db-model.trait', HasColumnAttribute::class)), '\\');
        $mcp = $this->confirm('Register the MCP server that documents the package to coding agents?', Config::boolean('db-model.mcp.enabled', true));
        $handle = $mcp ? $this->text('The handle the MCP server is registered under', Config::string('db-model.mcp.handle', 'db-model')) : Config::string('db-model.mcp.handle', 'db-model');
        $file = Installer::path();

        $this->components->twoColumnDetail($file, Installer::write(
            $file,
            Installer::configuration($namespace, $path, $trait, $mcp, $handle),
            fn (): bool => $this->confirm('['.$file.'] differs from these answers. Overwrite it?', true),
        ));

        Installer::apply($namespace, $path, $trait);

        $connection = $this->choose('The connection that reaches the databases', array_map(strval(...), array_keys(Config::array('database.connections'))), Config::string('database.default'));
        $databases = Installer::databases($connection);
        $schemas = [];

        foreach ($this->select($databases, $connection) as $database) {
            [$enum, $status] = Installer::schema($namespace, $database, $databases[$database] ?? '');

            $this->components->twoColumnDetail($enum, $status);

            $schemas[$enum] = $database;
        }

        if ($this->confirm('Generate the table enums now?', true)) {
            foreach ($schemas as $enum => $database) {
                $this->call('db-model:generate', ['--schema' => $enum, '--connection' => $connection, '--database' => $database]);
            }
        }

        $this->components->info('Installed. Run [db-model:check] to verify the PHP table enums still mirror the database.');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, string>  $databases
     * @return list<string>
     */
    private function select(array $databases, string $connection): array
    {
        $answer = $this->choice(
            'Which databases should be mirrored in PHP?',
            array_keys($databases),
            Config::string('database.connections.'.$connection.'.database'),
            multiple: true,
        );

        return array_values(array_map(strval(...), is_array($answer) ? $answer : [$answer]));
    }

    /** @param  list<string>  $choices */
    private function choose(string $question, array $choices, string $default): string
    {
        $answer = $this->choice($question, $choices, $default);

        return is_string($answer) ? $answer : $default;
    }

    private function text(string $question, string $default): string
    {
        $answer = $this->ask($question, $default);

        return is_string($answer) ? $answer : $default;
    }
}
