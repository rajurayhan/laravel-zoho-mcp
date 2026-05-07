<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Zoho;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Route;
use LaravelZohoMcp\Models\ZohoOAuthConnection;
use Mcp\Exception\ToolCallException;

final class ZohoOAuthService
{
    private ClientInterface $http;

    public function __construct(
        private readonly ConfigRepository $config,
        ?ClientInterface $http = null,
    ) {
        $this->http = $http ?? new Client(['timeout' => 60]);
    }

    public function buildAuthorizationUrl(string $state): string
    {
        $accountsUrl = rtrim((string) $this->config->get('zoho-mcp.accounts_url', 'https://accounts.zoho.com'), '/');
        $clientId = (string) $this->config->get('zoho-mcp.client_id', '');
        if ($clientId === '') {
            throw new \InvalidArgumentException('Zoho OAuth client id is not configured (ZOHO_CLIENT_ID).');
        }

        $scopes = $this->normalizedScopes();
        if ($scopes === '') {
            throw new \InvalidArgumentException('Zoho OAuth scopes are not configured (zoho-mcp.oauth.scopes).');
        }

        $redirectUri = $this->callbackAbsoluteUrl();

        $query = [
            'response_type' => 'code',
            'client_id' => $clientId,
            'scope' => $scopes,
            'redirect_uri' => $redirectUri,
            'access_type' => 'offline',
            'state' => $state,
        ];

        if ($this->config->get('zoho-mcp.oauth.prompt_consent', false)) {
            $query['prompt'] = 'consent';
        }

        return $accountsUrl.'/oauth/v2/auth?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * @return array<string, mixed>
     */
    public function exchangeAuthorizationCode(string $code): array
    {
        $accountsUrl = rtrim((string) $this->config->get('zoho-mcp.accounts_url', 'https://accounts.zoho.com'), '/');
        $clientId = (string) $this->config->get('zoho-mcp.client_id', '');
        $clientSecret = (string) $this->config->get('zoho-mcp.client_secret', '');
        if ($clientId === '' || $clientSecret === '') {
            throw new ToolCallException('Zoho OAuth client id or secret is not configured.');
        }

        $redirectUri = $this->callbackAbsoluteUrl();

        try {
            $response = $this->http->request('POST', $accountsUrl.'/oauth/v2/token', [
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'redirect_uri' => $redirectUri,
                    'code' => $code,
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new ToolCallException('Zoho authorization code exchange failed: '.$e->getMessage(), 0, $e);
        }

        $payload = json_decode((string) $response->getBody(), true);
        if (! is_array($payload)) {
            throw new ToolCallException('Zoho token endpoint returned invalid JSON.');
        }

        if (! isset($payload['access_token']) || ! is_string($payload['access_token'])) {
            $msg = isset($payload['error']) ? (string) $payload['error'] : 'unknown_error';
            $hint = isset($payload['error_description']) ? ': '.(string) $payload['error_description'] : '';

            throw new ToolCallException('Zoho rejected the authorization code ('.$msg.$hint.').');
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $tokenPayload
     */
    public function storeOrUpdateConnection(int|string $userId, array $tokenPayload): ZohoOAuthConnection
    {
        $accountsUrl = rtrim((string) $this->config->get('zoho-mcp.accounts_url', 'https://accounts.zoho.com'), '/');
        $apiBase = $this->normalizeApiBaseUrl(isset($tokenPayload['api_domain']) ? (string) $tokenPayload['api_domain'] : null);
        $expiresIn = isset($tokenPayload['expires_in']) && is_numeric($tokenPayload['expires_in']) ? (int) $tokenPayload['expires_in'] : 3600;
        $scope = isset($tokenPayload['scope']) && is_string($tokenPayload['scope']) ? $tokenPayload['scope'] : null;

        $existing = ZohoOAuthConnection::query()->where('user_id', $userId)->first();
        $refresh = isset($tokenPayload['refresh_token']) && is_string($tokenPayload['refresh_token']) && $tokenPayload['refresh_token'] !== ''
            ? $tokenPayload['refresh_token']
            : null;

        if ($refresh === null && $existing === null) {
            throw new ToolCallException('Zoho did not return a refresh token. Try again with prompt=consent enabled, or revoke the app in Zoho and reconnect.');
        }

        $attributes = [
            'user_id' => $userId,
            'access_token' => $tokenPayload['access_token'],
            'access_token_expires_at' => now()->addSeconds(max(60, $expiresIn)),
            'accounts_url' => $accountsUrl,
            'api_base_url' => $apiBase,
            'scope' => $scope,
        ];

        if ($refresh !== null) {
            $attributes['refresh_token'] = $refresh;
        }

        if ($existing !== null) {
            if ($refresh === null) {
                unset($attributes['refresh_token']);
            }
            $existing->fill($attributes);
            $existing->save();

            return $existing->refresh();
        }

        if ($refresh === null) {
            throw new ToolCallException('Missing refresh token for new Zoho connection.');
        }

        $attributes['refresh_token'] = $refresh;

        return ZohoOAuthConnection::query()->create($attributes);
    }

    private function callbackAbsoluteUrl(): string
    {
        $configured = $this->config->get('zoho-mcp.oauth.callback_url');
        if (is_string($configured) && $configured !== '') {
            return rtrim($configured, '/');
        }

        $name = (string) $this->config->get('zoho-mcp.oauth.callback_route_name', 'zoho-mcp.oauth.callback');

        if (Route::has($name)) {
            return route($name, absolute: true);
        }

        return rtrim(url('/'), '/').'/'.ltrim((string) $this->config->get('zoho-mcp.oauth.route_prefix', 'zoho-mcp'), '/').'/oauth/callback';
    }

    private function normalizedScopes(): string
    {
        $scopes = $this->config->get('zoho-mcp.oauth.scopes');
        if (is_string($scopes)) {
            return trim($scopes);
        }
        if (is_array($scopes)) {
            return implode(',', array_map(static fn ($s): string => is_string($s) ? trim($s) : '', $scopes));
        }

        return '';
    }

    private function normalizeApiBaseUrl(?string $apiDomain): string
    {
        if ($apiDomain === null || trim($apiDomain) === '') {
            return rtrim((string) $this->config->get('zoho-mcp.api_base_url', 'https://www.zohoapis.com'), '/');
        }

        $apiDomain = trim($apiDomain);
        if (str_starts_with($apiDomain, 'http://') || str_starts_with($apiDomain, 'https://')) {
            return rtrim($apiDomain, '/');
        }

        return 'https://'.rtrim($apiDomain, '/');
    }
}
