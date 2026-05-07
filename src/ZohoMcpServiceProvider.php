<?php

declare(strict_types=1);

namespace LaravelZohoMcp;

use Illuminate\Support\ServiceProvider;
use Laravel\Mcp\Facades\Mcp;
use LaravelZohoMcp\Console\ZohoMcpCommand;
use LaravelZohoMcp\Mcp\ZohoMcpServer;
use LaravelZohoMcp\Mcp\ZohoMcpTools;
use LaravelZohoMcp\Zoho\ZohoApiClient;
use LaravelZohoMcp\Zoho\ZohoOAuthService;

final class ZohoMcpServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/zoho-mcp.php', 'zoho-mcp');

        $this->app->singleton(ZohoOAuthService::class, function ($app) {
            return new ZohoOAuthService($app['config']);
        });

        $this->app->singleton(ZohoApiClient::class, function ($app) {
            return new ZohoApiClient($app['config']);
        });

        $this->app->singleton(ZohoMcpTools::class, function ($app) {
            return new ZohoMcpTools($app->make(ZohoApiClient::class));
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ((bool) config('zoho-mcp.oauth.register_routes', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }

        if (class_exists(Mcp::class)) {
            Mcp::local((string) config('zoho-mcp.mcp_local_handle', 'zoho'), ZohoMcpServer::class);
        }

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
