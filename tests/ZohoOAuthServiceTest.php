<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use LaravelZohoMcp\Models\ZohoOAuthConnection;
use LaravelZohoMcp\Tests\Fixtures\TestUser;
use LaravelZohoMcp\Zoho\ZohoOAuthService;

final class ZohoOAuthServiceTest extends TestCase
{
    public function test_exchanges_authorization_code(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'access_token' => 'at_val',
                'refresh_token' => 'rt_val',
                'expires_in' => 3600,
                'api_domain' => 'www.zohoapis.com',
            ], JSON_THROW_ON_ERROR)),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);
        $svc = new ZohoOAuthService($this->app['config'], $client);

        $out = $svc->exchangeAuthorizationCode('dummy_code');

        $this->assertSame('at_val', $out['access_token']);
        $this->assertSame('rt_val', $out['refresh_token']);
    }

    public function test_store_or_update_connection_persists_row(): void
    {
        $user = TestUser::query()->create([
            'email' => 'oauth-store@test.com',
            'name' => 'OAuth',
        ]);

        $svc = $this->app->make(ZohoOAuthService::class);
        $svc->storeOrUpdateConnection($user->id, [
            'access_token' => 'at',
            'refresh_token' => 'rt',
            'expires_in' => 3600,
            'api_domain' => 'www.zohoapis.com',
            'scope' => 'ZohoCRM.modules.READ',
        ]);

        $this->assertDatabaseHas('zoho_mcp_oauth_connections', ['user_id' => $user->id]);
        $row = ZohoOAuthConnection::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($row);
        $this->assertSame('rt', $row->refresh_token);
        $this->assertSame('https://www.zohoapis.com', $row->api_base_url);
    }
}
