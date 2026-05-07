<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('zoho_crm_create_records')]
#[Description('Create one or more CRM records. Each item in records is a Zoho CRM row object.')]
final class ZohoCrmCreateRecordsTool extends AbstractZohoTool
{
    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'module_api_name' => ['required', 'string'],
            'records' => ['required', 'array', 'min:1'],
            'records.*' => ['array'],
        ]);

        return $this->jsonResponse(fn () => $this->zohoMcpTools->zoho_crm_create_records(
            $data['module_api_name'],
            $data['records'],
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'module_api_name' => $schema->string()->required(),
            'records' => $schema->array()->min(1)->items($schema->object())->required()->description('Rows to create'),
        ];
    }
}
