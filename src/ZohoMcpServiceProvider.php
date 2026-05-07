<?php

declare(strict_types=1);

namespace LaravelZohoMcp;

use Illuminate\Support\ServiceProvider;
use LaravelZohoMcp\Console\ZohoMcpCommand;
use LaravelZohoMcp\Mcp\ZohoMcpTools;
use LaravelZohoMcp\Zoho\ZohoApiClient;

final class ZohoMcpServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/zoho-mcp.php', 'zoho-mcp');

        $this->app->singleton(ZohoApiClient::class, function ($app) {
            return new ZohoApiClient($app['config']);
        });

        $this->app->singleton(ZohoMcpTools::class, function ($app) {
            return new ZohoMcpTools($app->make(ZohoApiClient::class));
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/zoho-mcp.php' => config_path('zoho-mcp.php'),
            ], 'zoho-mcp-config');

            $this->commands([
                ZohoMcpCommand::class,
            ]);
        }
    }
}
