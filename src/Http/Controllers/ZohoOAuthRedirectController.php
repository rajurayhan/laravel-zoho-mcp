<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use LaravelZohoMcp\Zoho\ZohoOAuthService;

final class ZohoOAuthRedirectController
{
    public function __construct(
        private readonly ZohoOAuthService $oauth,
    ) {}

    public function __invoke(): RedirectResponse
    {
        $state = Str::random(48);
        Session::put((string) config('zoho-mcp.oauth.session_state_key', 'zoho_mcp_oauth_state'), $state);

        $url = $this->oauth->buildAuthorizationUrl($state);

        return redirect()->away($url);
    }
}
