# Db Model

Generates PHP artifacts to represent database schemas.

This is useful if you want a single source of truth for your database schema that you can reference in your codebase.

Install the package with `db-model:install`. 

Scaffold PHP representation of your database tables with `db-model:generate`. 
Check them with `db-model:check`.

## Requirements

- PHP `^8.5`
- [Laravel](https://laravel.com/) 13
- MySQL 8

## Installation

```bash
composer require zero-to-prod/db-model
```

### Configuration

CLI install:

```bash
php artisan db-model:install
```

Rerunning it is safe.

To publish the configuration file by itself instead:

```bash
php artisan vendor:publish --tag=db-model-config
```

`namespace` and `path` must describe the same place: a directory holding one
subdirectory per schema, named after it.

```php
'namespace' => 'App\\Sources\\Db',
'path' => app_path('Sources/Db'),
```

## Usage

Scaffold a table enum beside it for every table in the database:

```bash
php artisan db-model:generate            # writes to the configured path
php artisan db-model:generate --dry-run  # reports what it would write
```

```php
// app/Sources/Db/App/Users.php
namespace App\Sources\Db\App;

use ZeroToProd\DbModel\Column;
use ZeroToProd\DbModel\ColumnType;
use ZeroToProd\DbModel\HasColumnAttribute;
use ZeroToProd\DbModel\Table;

/**
 * @method string type()
 * @method string|null comment()
 * @method int|null length()
 * @method bool|null nullable()
 * @method bool|null unique()
 * @method bool|null primary_key()
 * @method bool|null auto_increment()
 */
#[Table(
    schema: App::class,
    attributes: [
        Table::name => 'users',
        Table::collate => 'utf8mb4_unicode_ci',
    ])]
enum Users: string
{
    use HasColumnAttribute;

    #[Column([
        Column::name => self::email,
        Column::comment => 'The users email',
        Column::type => ColumnType::varchar->value,
        Column::length => 255,
        Column::nullable => false,
        Column::unique => true,
    ])]
    case email = 'email';
}
```

```php
Users::email->value;      // 'email'
Users::email->rules();    // ['required', 'string', 'max:255']
Users::email->type();     // 'varchar'
Users::email->length();   // 255
Users::email->unique();   // true
Users::email->comment();  // 'The users email'
```

`rules()` states only what the schema itself constrains. Uniqueness,
confirmation and any format the database does not enforce belong to the
request:

```php
public function rules(): array
{
    return [
        'email' => [...Users::email->rules(), 'email', 'unique:users'],
        'password' => [...Users::password->rules(), 'confirmed'],
    ];
}
```

```php
// config/db-model.php
'trait' => App\Sources\Db\HasColumn::class,
```

```bash
php artisan db-model:generate   # the enums now `use HasColumn`
```

```php
Users::created_at->cast();   // 'immutable_datetime'
Users::created_at->rules();  // ['nullable', 'date'] — still from the package
```

### Interfaces

`implements` names an interface every generated table enum declares — a single
class-string, or a list of them. It defaults to `null`, which declares none:

```php
// config/db-model.php
'implements' => App\Sources\Db\Authority::class,
'implements' => [App\Sources\Db\Authority::class, App\Sources\Db\Sortable::class],
```

```bash
php artisan db-model:generate
```

```php
enum Users: string implements Authority, Sortable
{
    use HasColumn;
```

The generator writes the clause and the imports it needs. Satisfying the
interface is yours to do, on the `trait` above.

### Checking for drift

Add the check to your pipeline. It names every difference and exits non-zero:

```bash
php artisan db-model:check
```

Both commands take `--schema` to select the schema, defaulting to `App`, plus
`--connection` and `--database` to say where to read it from. Without them the
default connection and its own database are read:

```bash
php artisan db-model:check --schema=Reporting --connection=mysql --database=reporting
php artisan db-model:generate --schema=Reporting --database=reporting --path=/tmp/reporting
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

Three tools are exposed:

- `readme` — this document.
- `api` — the exact signature of every public class, property and method.
  Anything unlisted is internal and may change in any release.
- `install` — what `db-model:install` does, without a prompt to answer. Every
  argument defaults to the current setting, `databases` to the connection's
  own database. An unknown database name is answered with the list of real
  ones, and a `config/db-model.php` that says something else is left alone and
  reported until the call passes `overwrite: true`.

```json
{
  "namespace": "App\\Sources\\Db",
  "path": "app/Sources/Db",
  "implements": ["App\\Sources\\Db\\Authority"],
  "databases": ["app", "reporting"],
  "generate": true
}
```

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

The tests read a real MySQL schema. They create the database named by
`DB_DATABASE` if it does not exist, and drop every table in it. Point them at a
throwaway database — `phpunit.xml` defaults to `testing_db_model` on
`127.0.0.1:3306`:

```bash
DB_HOST=127.0.0.1 DB_PORT=3306 DB_DATABASE=testing_db_model \
DB_USERNAME=sail DB_PASSWORD=password composer test
```

## License

MIT. See [LICENSE](LICENSE).
