<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('zoho_crm_get_related_lists_metadata')]
#[Description('List related-list definitions for a module (GET /settings/related_lists). Use api_name from response with zoho_crm_get_related_records.')]
final class ZohoCrmGetRelatedListsMetadataTool extends AbstractZohoTool
{
    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'module_api_name' => ['required', 'string'],
            'layout_id' => ['nullable', 'string'],
        ]);

        return $this->jsonResponse(fn () => $this->zohoMcpTools->zoho_crm_get_related_lists_metadata(
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
