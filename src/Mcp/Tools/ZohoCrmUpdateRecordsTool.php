<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('zoho_crm_update_records')]
#[Description('Update CRM records. Each record must include the Zoho record id.')]
final class ZohoCrmUpdateRecordsTool extends AbstractZohoTool
{
    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'module_api_name' => ['required', 'string'],
            'records' => ['required', 'array', 'min:1'],
            'records.*' => ['array'],
        ]);

        return $this->jsonResponse(fn () => $this->zohoMcpTools->zoho_crm_update_records(
            $data['module_api_name'],
            $data['records'],
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'module_api_name' => $schema->string()->required(),
            'records' => $schema->array()->min(1)->items($schema->object())->required(),
        ];
    }
}
