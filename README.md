# Db Model

Generates PHP Artifacts to Represent Database Schemas

## Requirements

- PHP `^8.5`
- [Laravel](https://laravel.com/) 13

## Installation

```bash
composer require zero-to-prod/db-model
```

### Configuration

Publish the configuration file to override the defaults:

```bash
php artisan vendor:publish --tag=db-model-config
```

## Agent development

The package registers an [MCP](https://modelcontextprotocol.io/) server so
coding agents can read how it is meant to be used. It requires
[`laravel/mcp`](https://github.com/laravel/mcp), and registers nothing without
it.

```bash
composer require --dev laravel/mcp
php artisan mcp:start db-model
```

Register it with your agent:

```bash
claude mcp add db-model -- php artisan mcp:start db-model
```

Two tools are exposed:

- `readme` — this document.
- `api` — the exact signature of every public class, property and method.
  Anything unlisted is internal and may change in any release.

Point the handle somewhere else, or turn the server off, in
`config/db-model.php`:

```php
'mcp' => [
    'enabled' => true,
    'handle' => 'db-model',
],
```

## Development

```bash
composer check   # lint, rector, phpstan, 100% coverage, bc-check — mutates nothing
composer fix     # rector then pint
composer mcp list                      # the server's tools
composer mcp call api '{}'             # call one
```

`composer check` requires a coverage driver (Xdebug or pcov); without one Pest
cannot satisfy the `--min=100` gate.

## License

MIT. See [LICENSE](LICENSE).
