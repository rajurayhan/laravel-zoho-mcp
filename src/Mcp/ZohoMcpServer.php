<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Mcp;

use Illuminate\Container\Container;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Contracts\Transport;
use LaravelZohoMcp\Mcp\Tools\ZohoApiRequestTool;
use LaravelZohoMcp\Mcp\Tools\ZohoCrmCoqlQueryTool;
use LaravelZohoMcp\Mcp\Tools\ZohoCrmCreateRecordsTool;
use LaravelZohoMcp\Mcp\Tools\ZohoCrmDeleteRecordsTool;
use LaravelZohoMcp\Mcp\Tools\ZohoCrmGetRecordTool;
use LaravelZohoMcp\Mcp\Tools\ZohoCrmGetRecordsTool;
use LaravelZohoMcp\Mcp\Tools\ZohoCrmListModulesTool;
use LaravelZohoMcp\Mcp\Tools\ZohoCrmSearchRecordsTool;
use LaravelZohoMcp\Mcp\Tools\ZohoCrmUpdateRecordsTool;

final class ZohoMcpServer extends Server
{
    /**
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        ZohoApiRequestTool::class,
        ZohoCrmListModulesTool::class,
        ZohoCrmGetRecordsTool::class,
        ZohoCrmGetRecordTool::class,
        ZohoCrmCreateRecordsTool::class,
        ZohoCrmUpdateRecordsTool::class,
        ZohoCrmDeleteRecordsTool::class,
        ZohoCrmCoqlQueryTool::class,
        ZohoCrmSearchRecordsTool::class,
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
