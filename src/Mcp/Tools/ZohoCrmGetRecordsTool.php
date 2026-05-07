<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('zoho_crm_get_records')]
#[Description('List CRM records for a module with optional pagination and field projection.')]
final class ZohoCrmGetRecordsTool extends AbstractZohoTool
{
    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'module_api_name' => ['required', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
            'fields' => ['nullable', 'string'],
        ]);

        return $this->jsonResponse(fn () => $this->zohoMcpTools->zoho_crm_get_records(
            $data['module_api_name'],
            $data['page'] ?? null,
            $data['per_page'] ?? null,
            $data['fields'] ?? null,
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'module_api_name' => $schema->string()->required()->description('Zoho CRM module API name (e.g. Leads)'),
            'page' => $schema->integer()->min(1)->nullable()->description('Page number'),
            'per_page' => $schema->integer()->min(1)->max(200)->nullable()->description('Records per page'),
            'fields' => $schema->string()->nullable()->description('Comma-separated API field names'),
        ];
    }
}
