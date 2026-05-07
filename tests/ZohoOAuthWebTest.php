<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Tests;

use Illuminate\Support\Facades\Route;
use LaravelZohoMcp\Tests\Fixtures\TestUser;
use LaravelZohoMcp\Zoho\ZohoOAuthService;

final class ZohoOAuthWebTest extends TestCase
{
    public function test_registers_named_oauth_routes(): void
    {
        $this->assertTrue(Route::has('zoho-mcp.oauth.authorize'));
        $this->assertTrue(Route::has('zoho-mcp.oauth.callback'));
        $this->assertTrue(Route::has('zoho-mcp.mcp-tokens.store'));
    }

    public function test_authorize_redirects_to_zoho(): void
    {
        $user = TestUser::query()->create([
            'email' => 'authorize@test.com',
            'name' => 'A',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('zoho-mcp.oauth.authorize'));

        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        $this->assertStringContainsString('accounts.zoho.com/oauth/v2/auth', $location);
        $this->assertStringContainsString('test_zoho_client_id', $location);
    }

    public function test_build_authorization_url_contains_offline_access(): void
    {
        $svc = $this->app->make(ZohoOAuthService::class);
        $url = $svc->buildAuthorizationUrl('test_state_value');
        $this->assertStringContainsString('access_type=offline', $url);
        $this->assertStringContainsString('test_state_value', $url);
    }

    public function test_issues_mcp_access_token_for_authenticated_user(): void
    {
        $user = TestUser::query()->create([
            'email' => 'mcp-token@test.com',
            'name' => 'M',
        ]);

        $this->actingAs($user);

        $response = $this->postJson(route('zoho-mcp.mcp-tokens.store'), [
            'name' => 'cursor',
        ]);

        $response->assertCreated();
        $this->assertStringStartsWith('zmcp_', (string) $response->json('token'));
    }
}
