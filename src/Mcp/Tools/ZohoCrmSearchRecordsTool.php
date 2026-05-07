<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('zoho_crm_search_records')]
#[Description('Search CRM records using Zoho criteria syntax.')]
final class ZohoCrmSearchRecordsTool extends AbstractZohoTool
{
    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'module_api_name' => ['required', 'string'],
            'criteria' => ['required', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        return $this->jsonResponse(fn () => $this->zohoMcpTools->zoho_crm_search_records(
            $data['module_api_name'],
            $data['criteria'],
            $data['page'] ?? null,
            $data['per_page'] ?? null,
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'module_api_name' => $schema->string()->required(),
            'criteria' => $schema->string()->required()->description('Zoho criteria expression'),
            'page' => $schema->integer()->min(1)->nullable(),
            'per_page' => $schema->integer()->min(1)->max(200)->nullable(),
        ];
    }
}
