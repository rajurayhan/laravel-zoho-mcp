<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Mcp;

use Illuminate\Container\Container;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Contracts\Transport;
use LaravelZohoMcp\Mcp\Tools\ZohoApiRequestTool;
use LaravelZohoMcp\Mcp\Tools\ZohoCrmCoqlQueryTool;
use LaravelZohoMcp\Mcp\Tools\ZohoCrmCompositeRequestsTool;
use LaravelZohoMcp\Mcp\Tools\ZohoCrmCreateRecordsTool;
use LaravelZohoMcp\Mcp\Tools\ZohoCrmDeleteRecordsTool;
use LaravelZohoMcp\Mcp\Tools\ZohoCrmGetFieldsTool;
use LaravelZohoMcp\Mcp\Tools\ZohoCrmGetLayoutsTool;
use LaravelZohoMcp\Mcp\Tools\ZohoCrmGetModuleMetadataTool;
use LaravelZohoMcp\Mcp\Tools\ZohoCrmGetOrganizationTool;
use LaravelZohoMcp\Mcp\Tools\ZohoCrmGetProfileTool;
use LaravelZohoMcp\Mcp\Tools\ZohoCrmGetRecordTool;
use LaravelZohoMcp\Mcp\Tools\ZohoCrmGetRecordsTool;
use LaravelZohoMcp\Mcp\Tools\ZohoCrmGetRelatedListsMetadataTool;
use LaravelZohoMcp\Mcp\Tools\ZohoCrmGetRelatedRecordsTool;
use LaravelZohoMcp\Mcp\Tools\ZohoCrmGetRoleTool;
use LaravelZohoMcp\Mcp\Tools\ZohoCrmGetTerritoryTool;
use LaravelZohoMcp\Mcp\Tools\ZohoCrmGetUserTool;
use LaravelZohoMcp\Mcp\Tools\ZohoCrmListModulesTool;
use LaravelZohoMcp\Mcp\Tools\ZohoCrmListProfilesTool;
use LaravelZohoMcp\Mcp\Tools\ZohoCrmListRolesTool;
use LaravelZohoMcp\Mcp\Tools\ZohoCrmListTerritoriesTool;
use LaravelZohoMcp\Mcp\Tools\ZohoCrmListUsersTool;
use LaravelZohoMcp\Mcp\Tools\ZohoCrmSearchRecordsTool;
use LaravelZohoMcp\Mcp\Tools\ZohoCrmUpdateRecordsTool;

final class ZohoMcpServer extends Server
{
    /**
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        ZohoApiRequestTool::class,
        ZohoCrmGetOrganizationTool::class,
        ZohoCrmListModulesTool::class,
        ZohoCrmGetModuleMetadataTool::class,
        ZohoCrmGetFieldsTool::class,
        ZohoCrmGetLayoutsTool::class,
        ZohoCrmGetRelatedListsMetadataTool::class,
        ZohoCrmListUsersTool::class,
        ZohoCrmGetUserTool::class,
        ZohoCrmListRolesTool::class,
        ZohoCrmGetRoleTool::class,
        ZohoCrmListProfilesTool::class,
        ZohoCrmGetProfileTool::class,
        ZohoCrmListTerritoriesTool::class,
        ZohoCrmGetTerritoryTool::class,
        ZohoCrmGetRecordsTool::class,
        ZohoCrmGetRecordTool::class,
        ZohoCrmCreateRecordsTool::class,
        ZohoCrmUpdateRecordsTool::class,
        ZohoCrmDeleteRecordsTool::class,
        ZohoCrmCoqlQueryTool::class,
        ZohoCrmSearchRecordsTool::class,
        ZohoCrmGetRelatedRecordsTool::class,
        ZohoCrmCompositeRequestsTool::class,
    ];

    public function __construct(Transport $transport)
    {
        parent::__construct($transport);
    }

    protected function boot(): void
    {
        parent::boot();

        $app = Container::getInstance();

        if (! BootstrapZohoCredentials::configure($app)) {
            $msg = BootstrapZohoCredentials::error() ?? 'Zoho MCP could not bootstrap credentials.';
            fwrite(STDERR, $msg.PHP_EOL);
            exit(1);
        }

        $this->name = (string) config('zoho-mcp.server_name', 'Laravel Zoho MCP');
        $this->version = (string) config('zoho-mcp.server_version', '1.0.0');
        $this->instructions = (string) config('zoho-mcp.instructions', '');
    }
}
