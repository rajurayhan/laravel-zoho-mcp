<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Zoho;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use LaravelZohoMcp\Models\ZohoOAuthConnection;
use Mcp\Exception\ToolCallException;

final class ZohoApiClient
{
    private ClientInterface $http;

    private ?ZohoOAuthConnection $connection = null;

    private bool $legacyEnvironmentMode = false;

    private ?string $memoryAccessToken = null;

    private ?int $memoryAccessTokenExpiresAt = null;

    public function __construct(
        private readonly ConfigRepository $config,
        ?ClientInterface $http = null,
    ) {
        $this->http = $http ?? new Client(['timeout' => 120]);
    }

    public function useConnection(ZohoOAuthConnection $connection): void
    {
        $this->connection = $connection;
        $this->legacyEnvironmentMode = false;
        $this->memoryAccessToken = null;
        $this->memoryAccessTokenExpiresAt = null;
    }

    public function useLegacyEnvironmentCredentials(): void
    {
        $this->connection = null;
        $this->legacyEnvironmentMode = true;
        $this->memoryAccessToken = null;
        $this->memoryAccessTokenExpiresAt = null;
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>|null  $jsonBody
     * @return array<string, mixed>
     */
    public function request(string $method, string $path, array $query = [], ?array $jsonBody = null, bool $retryOnUnauthorized = true): array
    {
        $this->assertCredentialsConfigured();
        $uri = $this->apiBaseUrl().'/'.ltrim($path, '/');
        $options = [
            'headers' => [
                'Authorization' => 'Zoho-oauthtoken '.$this->getAccessToken(),
                'Accept' => 'application/json',
            ],
        ];
        if ($query !== []) {
            $options['query'] = $query;
        }
        if ($jsonBody !== null) {
            $options['json'] = $jsonBody;
        }

        try {
            $response = $this->http->request(strtoupper($method), $uri, $options);
        } catch (GuzzleException $e) {
            throw new ToolCallException('Zoho HTTP request failed: '.$e->getMessage(), 0, $e);
        }

        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        $decoded = $body !== '' ? json_decode($body, true) : null;

        if ($status === 401 && $retryOnUnauthorized) {
            $this->memoryAccessToken = null;
            $this->memoryAccessTokenExpiresAt = null;

            return $this->request($method, $path, $query, $jsonBody, false);
        }

        if (! is_array($decoded)) {
            throw new ToolCallException('Zoho returned a non-JSON response (HTTP '.$status.'): '.mb_substr($body, 0, 500));
        }

        if ($status >= 400) {
            throw new ToolCallException($this->formatZohoError($decoded, $status));
        }

        return $decoded;
    }

    public function crmPrefix(): string
    {
        $prefix = (string) $this->config->get('zoho-mcp.crm_api_prefix', 'crm/v8');

        return trim($prefix, '/');
    }

    private function apiBaseUrl(): string
    {
        if ($this->connection !== null) {
            return rtrim($this->connection->api_base_url, '/');
        }

        return rtrim((string) $this->config->get('zoho-mcp.api_base_url', 'https://www.zohoapis.com'), '/');
    }

    private function accountsUrl(): string
    {
        if ($this->connection !== null) {
            return rtrim($this->connection->accounts_url, '/');
        }

        return rtrim((string) $this->config->get('zoho-mcp.accounts_url', 'https://accounts.zoho.com'), '/');
    }

    private function assertCredentialsConfigured(): void
    {
        $clientId = (string) $this->config->get('zoho-mcp.client_id', '');
        $clientSecret = (string) $this->config->get('zoho-mcp.client_secret', '');
        if ($clientId === '' || $clientSecret === '') {
            throw new ToolCallException('Missing Zoho OAuth application credentials. Set ZOHO_CLIENT_ID and ZOHO_CLIENT_SECRET.');
        }

        if ($this->connection !== null) {
            if ($this->connection->refresh_token === null || $this->connection->refresh_token === '') {
                throw new ToolCallException('Linked Zoho account is missing a refresh token. Reconnect via the web OAuth flow.');
            }

            return;
        }

        if ($this->legacyEnvironmentMode) {
            $refresh = (string) $this->config->get('zoho-mcp.refresh_token', '');
            if ($refresh === '') {
                throw new ToolCallException('Missing Zoho refresh token. Set ZOHO_REFRESH_TOKEN for legacy mode.');
            }

            return;
        }

        throw new ToolCallException('Zoho API client is not configured. Use zoho:mcp with ZOHO_MCP_ACCESS_TOKEN or legacy env credentials.');
    }

    private function getAccessToken(): string
    {
        if ($this->connection !== null) {
            $expires = $this->connection->access_token_expires_at;
            if (
                $this->connection->access_token !== null
                && $this->connection->access_token !== ''
                && $expires !== null
                && $expires->isFuture()
                && $expires->greaterThan(now()->addMinute())
            ) {
                return (string) $this->connection->access_token;
            }
        }

        $now = time();
        if ($this->memoryAccessToken !== null && ($this->memoryAccessTokenExpiresAt === null || $now < $this->memoryAccessTokenExpiresAt - 60)) {
            return (string) $this->memoryAccessToken;
        }

        $this->refreshAccessToken();

        return (string) $this->memoryAccessToken;
    }

    private function refreshAccessToken(): void
    {
        $this->assertCredentialsConfigured();
        $tokenUri = $this->accountsUrl().'/oauth/v2/token';
        $form = [
            'client_id' => (string) $this->config->get('zoho-mcp.client_id'),
            'client_secret' => (string) $this->config->get('zoho-mcp.client_secret'),
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->refreshToken(),
        ];

        try {
            $response = $this->http->request('POST', $tokenUri, [
                'form_params' => $form,
            ]);
        } catch (GuzzleException $e) {
            throw new ToolCallException('Zoho token refresh failed: '.$e->getMessage(), 0, $e);
        }

        $payload = json_decode((string) $response->getBody(), true);
        if (! is_array($payload)) {
            throw new ToolCallException('Zoho token endpoint returned invalid JSON.');
        }
        if (! isset($payload['access_token']) || ! is_string($payload['access_token'])) {
            $msg = isset($payload['error']) ? (string) $payload['error'] : 'unknown_error';
            $hint = isset($payload['error_description']) ? ': '.(string) $payload['error_description'] : '';

            throw new ToolCallException('Zoho token refresh rejected ('.$msg.$hint.').');
        }

        $this->memoryAccessToken = $payload['access_token'];
        $expiresIn = isset($payload['expires_in']) && is_numeric($payload['expires_in']) ? (int) $payload['expires_in'] : 3600;
        $this->memoryAccessTokenExpiresAt = time() + max(60, $expiresIn);

        if ($this->connection !== null) {
            $this->connection->access_token = $payload['access_token'];
            $this->connection->access_token_expires_at = now()->addSeconds(max(60, $expiresIn));
            if (isset($payload['api_domain']) && is_string($payload['api_domain']) && $payload['api_domain'] !== '') {
                $this->connection->api_base_url = $this->normalizeApiBaseUrlFromToken($payload['api_domain']);
            }
            $this->connection->save();
        }
    }

    private function refreshToken(): string
    {
        if ($this->connection !== null) {
            return (string) $this->connection->refresh_token;
        }

        return (string) $this->config->get('zoho-mcp.refresh_token', '');
    }

    private function normalizeApiBaseUrlFromToken(string $apiDomain): string
    {
        $apiDomain = trim($apiDomain);
        if (str_starts_with($apiDomain, 'http://') || str_starts_with($apiDomain, 'https://')) {
            return rtrim($apiDomain, '/');
        }

        return 'https://'.rtrim($apiDomain, '/');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function formatZohoError(array $payload, int $status): string
    {
        $parts = ['Zoho API error (HTTP '.$status.')'];
        if (isset($payload['message']) && is_string($payload['message'])) {
            $parts[] = $payload['message'];
        }
        if (isset($payload['code']) && (is_string($payload['code']) || is_int($payload['code']))) {
            $parts[] = 'code: '.(string) $payload['code'];
        }
        if (isset($payload['details']) && is_array($payload['details'])) {
            $enc = json_encode($payload['details'], JSON_UNESCAPED_SLASHES);
            if ($enc !== false) {
                $parts[] = 'details: '.$enc;
            }
        }

        return implode(' — ', $parts);
    }
}
