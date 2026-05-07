<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use LaravelZohoMcp\Zoho\ZohoOAuthService;

final class ZohoOAuthCallbackController
{
    public function __construct(
        private readonly ZohoOAuthService $oauth,
    ) {}

    public function __invoke(Request $request): RedirectResponse|\Illuminate\Http\Response
    {
        if ($request->filled('error')) {
            $description = $request->string('error_description')->toString();

            return response(
                'Zoho authorization failed: '.$request->string('error')->toString().($description !== '' ? ' — '.$description : ''),
                400
            );
        }

        $sessionKey = (string) config('zoho-mcp.oauth.session_state_key', 'zoho_mcp_oauth_state');
        $expected = Session::pull($sessionKey);
        if (! is_string($expected) || $expected === '' || ! $request->has('state') || $request->string('state')->toString() !== $expected) {
            return response('Invalid OAuth state. Restart the connect flow.', 403);
        }

        $code = $request->string('code')->toString();
        if ($code === '') {
            return response('Missing authorization code.', 400);
        }

        $userId = Auth::id();
        if ($userId === null) {
            return response('You must be signed in to complete Zoho connection.', 401);
        }

        $tokens = $this->oauth->exchangeAuthorizationCode($code);
        $this->oauth->storeOrUpdateConnection($userId, $tokens);

        $target = (string) config('zoho-mcp.oauth.completion_redirect', '/');

        return redirect()->to($target)->with('zoho_mcp_connected', true);
    }
}
