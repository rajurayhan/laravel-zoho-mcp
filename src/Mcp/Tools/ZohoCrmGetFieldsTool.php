<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('zoho_crm_get_fields')]
#[Description('List field definitions for a module (GET /settings/fields). Optional layout_id.')]
final class ZohoCrmGetFieldsTool extends AbstractZohoTool
{
    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'module_api_name' => ['required', 'string'],
            'layout_id' => ['nullable', 'string'],
        ]);

        return $this->jsonResponse(fn () => $this->zohoMcpTools->zoho_crm_get_fields(
            $data['module_api_name'],
            $data['layout_id'] ?? null,
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'module_api_name' => $schema->string()->required(),
            'layout_id' => $schema->string()->nullable(),
        ];
    }
}
