<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('zoho_crm_delete_records')]
#[Description('Delete CRM records by id.')]
final class ZohoCrmDeleteRecordsTool extends AbstractZohoTool
{
    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'module_api_name' => ['required', 'string'],
            'record_ids' => ['required', 'array', 'min:1'],
            'record_ids.*' => ['string'],
        ]);

        return $this->jsonResponse(fn () => $this->zohoMcpTools->zoho_crm_delete_records(
            $data['module_api_name'],
            $data['record_ids'],
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'module_api_name' => $schema->string()->required(),
            'record_ids' => $schema->array()->min(1)->items($schema->string())->required(),
        ];
    }
}
