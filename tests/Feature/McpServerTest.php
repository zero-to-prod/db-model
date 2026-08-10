<?php

declare(strict_types=1);

use Laravel\Mcp\Server\Registrar;
use ZeroToProd\DbModel\Internal\Mcp\Server;
use ZeroToProd\DbModel\Internal\Mcp\Tools\Readme;

it('registers the server under the db-model handle', function (): void {
    expect($this->app->make(Registrar::class)->getLocalServer('db-model'))->not->toBeNull();
});

it('registers nothing when the server is disabled', function (): void {
    $this->withConfig(['db-model.mcp.enabled' => false]);

    expect($this->app->make(Registrar::class)->getLocalServer('db-model'))->toBeNull();
});

it('registers under a configured handle', function (): void {
    $this->withConfig(['db-model.mcp.handle' => 'package-docs']);

    $registrar = $this->app->make(Registrar::class);

    expect($registrar->getLocalServer('package-docs'))->not->toBeNull()
        ->and($registrar->getLocalServer('db-model'))->toBeNull();
});

it('returns the readme', function (): void {
    Server::tool(Readme::class)
        ->assertOk()
        ->assertHasNoErrors()
        ->assertName('readme')
        ->assertSee(file_get_contents(Readme::path()));
});

it('describes the tool so an agent knows when to call it', function (): void {
    $tool = new Readme;

    expect($tool->name())->toBe('readme')
        ->and($tool->description())->toContain('README');
});
