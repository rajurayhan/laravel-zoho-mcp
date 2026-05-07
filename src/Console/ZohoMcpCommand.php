<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Console;

use Illuminate\Console\Command;
use LaravelZohoMcp\Mcp\ZohoMcpProcessContext;

final class ZohoMcpCommand extends Command
{
    protected $signature = 'zoho:mcp {--token= : MCP access token (overrides ZOHO_MCP_ACCESS_TOKEN for this process)}';

    protected $description = 'Start the Zoho MCP server (delegates to mcp:start using Laravel MCP)';

    public function handle(): int
    {
        $token = $this->option('token');
        ZohoMcpProcessContext::$mcpAccessTokenOverride = is_string($token) && $token !== '' ? $token : null;

        return $this->call('mcp:start', [
            'handle' => (string) config('zoho-mcp.mcp_local_handle', 'zoho'),
        ]);
    }
}
