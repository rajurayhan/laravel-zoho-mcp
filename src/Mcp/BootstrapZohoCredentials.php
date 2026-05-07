<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Mcp;

use Illuminate\Contracts\Foundation\Application;
use LaravelZohoMcp\Models\ZohoMcpAccessToken;
use LaravelZohoMcp\Models\ZohoOAuthConnection;
use LaravelZohoMcp\Zoho\ZohoApiClient;

final class BootstrapZohoCredentials
{
    private static ?string $error = null;

    public static function error(): ?string
    {
        return self::$error;
    }

    public static function configure(Application $app): bool
    {
        self::$error = null;

        $plain = (string) (ZohoMcpProcessContext::$mcpAccessTokenOverride ?: env('ZOHO_MCP_ACCESS_TOKEN', ''));
        $api = $app->make(ZohoApiClient::class);

        if ($plain !== '') {
            $access = ZohoMcpAccessToken::findValidFromPlain($plain);
            if ($access === null) {
                self::$error = 'Invalid or expired ZOHO_MCP_ACCESS_TOKEN. Create a new token (POST /'.trim((string) config('zoho-mcp.oauth.route_prefix', 'zoho-mcp'), '/').'/mcp-access-tokens).';

                return false;
            }

            $access->forceFill(['last_used_at' => now()])->save();

            $connection = ZohoOAuthConnection::query()->where('user_id', $access->user_id)->first();
            if ($connection === null) {
                self::$error = 'No Zoho account linked for this user. Open /'.trim((string) config('zoho-mcp.oauth.route_prefix', 'zoho-mcp'), '/').'/oauth/authorize while signed in.';

                return false;
            }

            $api->useConnection($connection);

            return true;
        }

        $clientId = (string) config('zoho-mcp.client_id', '');
        $secret = (string) config('zoho-mcp.client_secret', '');
        $refresh = (string) config('zoho-mcp.refresh_token', '');
        if ($clientId !== '' && $secret !== '' && $refresh !== '') {
            $api->useLegacyEnvironmentCredentials();

            return true;
        }

        self::$error = 'Set ZOHO_MCP_ACCESS_TOKEN or legacy ZOHO_REFRESH_TOKEN with ZOHO_CLIENT_ID and ZOHO_CLIENT_SECRET.';

        return false;
    }
}
