<?php

declare(strict_types=1);

use ZeroToProd\DbModel\HasColumnAttribute;

return [

    /*
    |--------------------------------------------------------------------------
    | Source Artifacts
    |--------------------------------------------------------------------------
    |
    | Where the PHP table enums live. `namespace` and `path` must describe the
    | same place: a directory holding one subdirectory per schema, named after
    | it. `--schema=App` therefore resolves to:
    |
    |     App\Sources\Db\App\App   the enum carrying the #[Schema] attribute
    |     app/Sources/Db/App/*.php the enums carrying the #[Table] attribute
    |
    */

    'namespace' => 'App\\Sources\\Db',

    'path' => app_path('Sources/Db'),

    /*
    |--------------------------------------------------------------------------
    | Column Trait
    |--------------------------------------------------------------------------
    |
    | The trait every generated table enum uses. The default reads the
    | #[Column] attribute and derives the validation rules from it.
    |
    | Anything else a column implies — an OpenApi schema, an Eloquent cast — is
    | yours to define. Point this at a trait of your own that uses
    | HasColumnAttribute, and the generator will use it instead.
    |
    */

    'trait' => HasColumnAttribute::class,

    /*
    |--------------------------------------------------------------------------
    | Enum Interfaces
    |--------------------------------------------------------------------------
    |
    | The interface every generated table enum implements. Null declares none,
    | which is the default. A single class-string, or a list of them:
    |
    |     'implements' => App\Sources\Db\Authority::class,
    |     'implements' => [App\Sources\Db\Authority::class, Stringable::class],
    |
    | The generator writes the `implements` clause and the imports it needs.
    | Satisfying the interface is yours to do, on the trait above.
    |
    */

    'implements' => null,

    /*
    |--------------------------------------------------------------------------
    | MCP Server
    |--------------------------------------------------------------------------
    |
    | The package registers an MCP server so coding agents can read how it is
    | meant to be used. It requires laravel/mcp, and is a no-op without it:
    |
    |     composer require --dev laravel/mcp
    |     php artisan mcp:start db-model
    |
    | The `handle` is the name the server is registered under, which is the
    | argument to `mcp:start` and the name your agent refers to it by.
    |
    */

    'mcp' => [
        'enabled' => true,
        'handle' => 'db-model',
    ],

];
