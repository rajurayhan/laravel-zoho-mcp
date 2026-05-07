<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Zoho;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Mcp\Exception\ToolCallException;

final class ZohoApiClient
{
    private ClientInterface $http;

    private string $accountsUrl;

    private string $apiBaseUrl;

    private ?string $clientId;

    private ?string $clientSecret;

    private ?string $refreshToken;

    private ?string $accessToken = null;

    private ?int $accessTokenExpiresAt = null;

    public function __construct(
        private readonly ConfigRepository $config,
        ?ClientInterface $http = null,
    ) {
        $this->http = $http ?? new Client(['timeout' => 120]);
        $cfg = $config->get('zoho-mcp', []);
        $this->accountsUrl = rtrim((string) ($cfg['accounts_url'] ?? 'https://accounts.zoho.com'), '/');
        $this->apiBaseUrl = rtrim((string) ($cfg['api_base_url'] ?? 'https://www.zohoapis.com'), '/');
        $this->clientId = isset($cfg['client_id']) ? (string) $cfg['client_id'] : null;
        $this->clientSecret = isset($cfg['client_secret']) ? (string) $cfg['client_secret'] : null;
        $this->refreshToken = isset($cfg['refresh_token']) ? (string) $cfg['refresh_token'] : null;
    }

    /**
     * Perform an HTTP request against the configured Zoho API base URL.
     *
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>|null  $jsonBody
     * @return array<string, mixed>
     */
    public function request(string $method, string $path, array $query = [], ?array $jsonBody = null, bool $retryOnUnauthorized = true): array
    {
        $this->assertCredentialsConfigured();
        $uri = $this->apiBaseUrl.'/'.ltrim($path, '/');
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
            $this->accessToken = null;
            $this->accessTokenExpiresAt = null;

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

    private function assertCredentialsConfigured(): void
    {
        if ($this->clientId === null || $this->clientId === '') {
            throw new ToolCallException('Missing Zoho OAuth client id. Set ZOHO_CLIENT_ID in your environment.');
        }
        if ($this->clientSecret === null || $this->clientSecret === '') {
            throw new ToolCallException('Missing Zoho OAuth client secret. Set ZOHO_CLIENT_SECRET in your environment.');
        }
        if ($this->refreshToken === null || $this->refreshToken === '') {
            throw new ToolCallException('Missing Zoho refresh token. Set ZOHO_REFRESH_TOKEN in your environment.');
        }
    }

    private function getAccessToken(): string
    {
        $now = time();
        if ($this->accessToken !== null && ($this->accessTokenExpiresAt === null || $now < $this->accessTokenExpiresAt - 60)) {
            return $this->accessToken;
        }

        $this->refreshAccessToken();

        return (string) $this->accessToken;
    }

    private function refreshAccessToken(): void
    {
        $this->assertCredentialsConfigured();
        $tokenUri = $this->accountsUrl.'/oauth/v2/token';
        try {
            $response = $this->http->request('POST', $tokenUri, [
                'form_params' => [
                    'refresh_token' => $this->refreshToken,
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'grant_type' => 'refresh_token',
                ],
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

        $this->accessToken = $payload['access_token'];
        $expiresIn = isset($payload['expires_in']) && is_numeric($payload['expires_in']) ? (int) $payload['expires_in'] : 3600;
        $this->accessTokenExpiresAt = time() + max(60, $expiresIn);
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
