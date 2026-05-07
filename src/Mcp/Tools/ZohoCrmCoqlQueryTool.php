<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('zoho_crm_coql_query')]
#[Description('Run a read-only COQL query against Zoho CRM.')]
final class ZohoCrmCoqlQueryTool extends AbstractZohoTool
{
    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'select_query' => ['required', 'string'],
        ]);

        return $this->jsonResponse(fn () => $this->zohoMcpTools->zoho_crm_coql_query($data['select_query']));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'select_query' => $schema->string()->required()->description('COQL select statement'),
        ];
    }
}
