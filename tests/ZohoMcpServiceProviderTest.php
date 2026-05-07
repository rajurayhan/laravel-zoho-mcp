<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Tests;

use Illuminate\Support\Facades\Artisan;
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
}
