<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Tests;

use Illuminate\Support\Facades\Artisan;
use Laravel\Mcp\Facades\Mcp;
use LaravelZohoMcp\Console\ZohoMcpCommand;
use LaravelZohoMcp\Mcp\ZohoMcpTools;
use LaravelZohoMcp\Zoho\ZohoApiClient;

final class ZohoMcpServiceProviderTest extends TestCase
{
    public function test_registers_zoho_mcp_command(): void
    {
        $this->assertInstanceOf(ZohoMcpCommand::class, Artisan::all()['zoho:mcp'] ?? null);
    }

    public function test_resolves_tools_and_api_client(): void
    {
        $this->assertInstanceOf(ZohoApiClient::class, $this->app->make(ZohoApiClient::class));
        $this->assertInstanceOf(ZohoMcpTools::class, $this->app->make(ZohoMcpTools::class));
    }

    public function test_registers_laravel_mcp_local_server(): void
    {
        $this->assertNotNull(Mcp::getLocalServer('zoho'));
    }

    public function test_registers_mcp_start_command(): void
    {
        $this->assertArrayHasKey('mcp:start', Artisan::all());
    }
}
