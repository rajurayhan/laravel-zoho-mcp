<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Console;

use Illuminate\Console\Command;
use LaravelZohoMcp\Mcp\ZohoMcpTools;
use LaravelZohoMcp\Models\ZohoMcpAccessToken;
use LaravelZohoMcp\Models\ZohoOAuthConnection;
use LaravelZohoMcp\Zoho\ZohoApiClient;
use Mcp\Server;
use Mcp\Server\Transport\StdioTransport;

final class ZohoMcpCommand extends Command
{
    protected $signature = 'zoho:mcp {--token= : MCP access token (overrides ZOHO_MCP_ACCESS_TOKEN)}';

    protected $description = 'Start the Zoho MCP server (stdio transport for Cursor, Claude, and other MCP clients)';

    public function handle(): int
    {
        $plain = (string) ($this->option('token') ?: env('ZOHO_MCP_ACCESS_TOKEN', ''));
        $api = $this->laravel->make(ZohoApiClient::class);

        if ($plain !== '') {
            $access = ZohoMcpAccessToken::findValidFromPlain($plain);
            if ($access === null) {
                $this->error('Invalid or expired MCP access token. Create a new token while signed in to your app (POST zoho-mcp/mcp-access-tokens).');

                return self::FAILURE;
            }

            $access->forceFill(['last_used_at' => now()])->save();

            $connection = ZohoOAuthConnection::query()->where('user_id', $access->user_id)->first();
            if ($connection === null) {
                $this->error('No Zoho account linked for this user. Sign in to your web app, open /'.trim((string) config('zoho-mcp.oauth.route_prefix', 'zoho-mcp'), '/').'/oauth/authorize, and complete Zoho consent.');

                return self::FAILURE;
            }

            $api->useConnection($connection);
        } else {
            $clientId = (string) config('zoho-mcp.client_id', '');
            $secret = (string) config('zoho-mcp.client_secret', '');
            $refresh = (string) config('zoho-mcp.refresh_token', '');
            if ($clientId !== '' && $secret !== '' && $refresh !== '') {
                $api->useLegacyEnvironmentCredentials();
            } else {
                $this->error('Multi-user mode: set ZOHO_MCP_ACCESS_TOKEN (issue via POST /'.trim((string) config('zoho-mcp.oauth.route_prefix', 'zoho-mcp'), '/').'/mcp-access-tokens while authenticated). Legacy mode: set ZOHO_REFRESH_TOKEN plus client credentials.');

                return self::FAILURE;
            }
        }

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
