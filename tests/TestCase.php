<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaravelZohoMcp\Tests\Fixtures\TestUser;
use LaravelZohoMcp\ZohoMcpServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [ZohoMcpServiceProvider::class];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('app.url', 'https://zoho-mcp.test');

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        $app['config']->set('auth.defaults.guard', 'web');
        $app['config']->set('auth.providers.users.driver', 'eloquent');
        $app['config']->set('auth.providers.users.model', TestUser::class);

        $app['config']->set('zoho-mcp.client_id', 'test_zoho_client_id');
        $app['config']->set('zoho-mcp.client_secret', 'test_zoho_client_secret');
        $app['config']->set('zoho-mcp.oauth.callback_url', 'https://zoho-mcp.test/zoho-mcp/oauth/callback');
        $app['config']->set('zoho-mcp.oauth.scopes', 'ZohoCRM.modules.READ');
    }
}
