<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Console;

use Illuminate\Console\Command;
use LaravelZohoMcp\Mcp\ZohoMcpTools;
use Mcp\Server;
use Mcp\Server\Transport\StdioTransport;

final class ZohoMcpCommand extends Command
{
    protected $signature = 'zoho:mcp';

    protected $description = 'Start the Zoho MCP server (stdio transport for Cursor, Claude, and other MCP clients)';

    public function handle(): int
    {
        $config = $this->laravel['config'];

        $builder = Server::builder()
            ->setContainer($this->laravel)
            ->setServerInfo(
                (string) $config->get('zoho-mcp.server_name', 'Laravel Zoho MCP'),
                (string) $config->get('zoho-mcp.server_version', '1.0.0'),
            );

        $instructions = $config->get('zoho-mcp.instructions');
        if (is_string($instructions) && $instructions !== '') {
            $builder->setInstructions($instructions);
        }

        $tools = ZohoMcpTools::class;
        $builder
            ->addTool([$tools, 'zoho_api_request'], 'zoho_api_request')
            ->addTool([$tools, 'zoho_crm_list_modules'], 'zoho_crm_list_modules')
            ->addTool([$tools, 'zoho_crm_get_records'], 'zoho_crm_get_records')
            ->addTool([$tools, 'zoho_crm_get_record'], 'zoho_crm_get_record')
            ->addTool([$tools, 'zoho_crm_create_records'], 'zoho_crm_create_records')
            ->addTool([$tools, 'zoho_crm_update_records'], 'zoho_crm_update_records')
            ->addTool([$tools, 'zoho_crm_delete_records'], 'zoho_crm_delete_records')
            ->addTool([$tools, 'zoho_crm_coql_query'], 'zoho_crm_coql_query')
            ->addTool([$tools, 'zoho_crm_search_records'], 'zoho_crm_search_records');

        $server = $builder->build();
        $server->run(new StdioTransport());

        return self::SUCCESS;
    }
}
