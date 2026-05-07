<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Mcp;

/**
 * Holds per-process overrides for MCP stdio (e.g. artisan zoho:mcp --token=...).
 */
final class ZohoMcpProcessContext
{
    public static ?string $mcpAccessTokenOverride = null;
}
