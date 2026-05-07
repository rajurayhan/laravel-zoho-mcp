<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use LaravelZohoMcp\Http\Controllers\ZohoMcpAccessTokenController;
use LaravelZohoMcp\Http\Controllers\ZohoOAuthCallbackController;
use LaravelZohoMcp\Http\Controllers\ZohoOAuthRedirectController;

$prefix = (string) config('zoho-mcp.oauth.route_prefix', 'zoho-mcp');

/** @var array<int, string> $middleware */
$middleware = config('zoho-mcp.oauth.middleware', ['web', 'auth']);
if (! is_array($middleware)) {
    $middleware = ['web', 'auth'];
}

Route::middleware($middleware)->prefix($prefix)->group(function (): void {
    Route::get('/oauth/authorize', ZohoOAuthRedirectController::class)
        ->name('zoho-mcp.oauth.authorize');

    Route::get('/oauth/callback', ZohoOAuthCallbackController::class)
        ->name('zoho-mcp.oauth.callback');

    Route::post('/mcp-access-tokens', [ZohoMcpAccessTokenController::class, 'store'])
        ->name('zoho-mcp.mcp-tokens.store');
});
