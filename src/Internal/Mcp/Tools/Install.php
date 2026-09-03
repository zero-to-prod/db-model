<?php

declare(strict_types=1);

namespace ZeroToProd\DbModel\Internal\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Override;
use ZeroToProd\DbModel\HasColumnAttribute;
use ZeroToProd\DbModel\Internal\Installer;

/** @internal */
#[IsIdempotent]
class Install extends Tool
{
    protected string $description = 'Installs the package: writes config/db-model.php, the #[Schema] enum for each database, and the table enums. Mirrors the db-model:install command.';

    /** @return array<string, mixed> */
    #[Override]
    public function schema(JsonSchema $schema): array
    {
        return [
            'namespace' => $schema->string()
                ->description('The namespace the generated artifacts live under. Defaults to the current setting.'),
            'path' => $schema->string()
                ->description('The directory they live in, relative to the project root. Must describe the same place as the namespace. Defaults to the current setting.'),
            'trait' => $schema->string()
                ->description('The trait every generated table enum uses. Defaults to the current setting.'),
            'implements' => $schema->array()->items($schema->string())
                ->description('The interfaces every generated table enum implements. Defaults to the current setting; an empty list declares none.'),
            'mcp_enabled' => $schema->boolean()
                ->description('Register the MCP server that documents the package to coding agents. Defaults to the current setting.'),
            'mcp_handle' => $schema->string()
                ->description('The handle the MCP server is registered under. Defaults to the current setting.'),
            'connection' => $schema->string()
                ->description('The connection that reaches the databases. Defaults to the application default.'),
            'databases' => $schema->array()->items($schema->string())
                ->description("The databases to mirror in PHP, one #[Schema] enum each. Defaults to the connection's own database. Call with an unknown name to be told what there is."),
            'generate' => $schema->boolean()
                ->description('Generate the table enums as well. Defaults to true.'),
            'overwrite' => $schema->boolean()
                ->description('Rewrite config/db-model.php when it already says something else. Defaults to false, which keeps the file as it is.'),
        ];
    }

    public function handle(Request $request): Response
    {
        $namespace = trim($this->text($request, 'namespace', Config::string('db-model.namespace')), '\\');
        $path = rtrim($this->text($request, 'path', Installer::relative(Config::string('db-model.path'))), '/');
        $trait = trim($this->text($request, 'trait', Config::string('db-model.trait', HasColumnAttribute::class)), '\\');
        $implements = Installer::interfaces($request->has('implements') ? $request->array('implements') : Config::get('db-model.implements'));
        $mcp = $this->flag($request, 'mcp_enabled', Config::boolean('db-model.mcp.enabled', true));
        $handle = $this->text($request, 'mcp_handle', Config::string('db-model.mcp.handle', 'db-model'));
        $connection = $this->text($request, 'connection', Config::string('database.default'));
        $databases = Installer::databases($connection);
        $selected = $request->has('databases')
            ? array_values(array_map(strval(...), $request->array('databases')))
            : [Config::string('database.connections.'.$connection.'.database')];
        $unknown = array_diff($selected, array_keys($databases));

        if ($unknown !== []) {
            return Response::error(sprintf(
                'Connection [%s] has no database named [%s]. It has: %s.',
                $connection,
                implode('], [', $unknown),
                implode(', ', array_keys($databases)),
            ));
        }

        $file = Installer::path();
        $lines = [$file.' '.Installer::write(
            $file,
            Installer::configuration($namespace, $path, $trait, $implements, $mcp, $handle),
            static fn (): bool => $request->boolean('overwrite'),
        ).'.'];

        Installer::apply($namespace, $path, $trait, $implements);

        $generate = $this->flag($request, 'generate', true);

        foreach ($selected as $database) {
            [$enum, $status] = Installer::schema($namespace, $database, $databases[$database]);

            $lines[] = $namespace.'\\'.$enum.'\\'.$enum.' '.$status.'.';

            if ($generate) {
                Artisan::call('db-model:generate', ['--schema' => $enum, '--connection' => $connection, '--database' => $database]);

                $lines[] = trim(Artisan::output());
            }
        }

        return Response::text(implode("\n", [...$lines, 'Run [db-model:check] to verify the PHP table enums still mirror the database.']));
    }

    private function text(Request $request, string $key, string $default): string
    {
        return $request->has($key) ? $request->string($key)->toString() : $default;
    }

    private function flag(Request $request, string $key, bool $default): bool
    {
        return $request->has($key) ? $request->boolean($key) : $default;
    }
}
