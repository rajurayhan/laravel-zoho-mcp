<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('zoho_crm_get_record')]
#[Description('Fetch a single CRM record by id.')]
final class ZohoCrmGetRecordTool extends AbstractZohoTool
{
    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'module_api_name' => ['required', 'string'],
            'record_id' => ['required', 'string'],
            'fields' => ['nullable', 'string'],
        ]);

        return $this->jsonResponse(fn () => $this->zohoMcpTools->zoho_crm_get_record(
            $data['module_api_name'],
            $data['record_id'],
            $data['fields'] ?? null,
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'module_api_name' => $schema->string()->required(),
            'record_id' => $schema->string()->required(),
            'fields' => $schema->string()->nullable(),
        ];
    }
}
