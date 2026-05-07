<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('zoho_crm_list_territories')]
#[Description('List CRM territories (GET /settings/territories) when territory management is enabled.')]
final class ZohoCrmListTerritoriesTool extends AbstractZohoTool
{
    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:2000'],
        ]);

        return $this->jsonResponse(fn () => $this->zohoMcpTools->zoho_crm_list_territories(
            $data['page'] ?? null,
            $data['per_page'] ?? null,
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'page' => $schema->integer()->min(1)->nullable(),
            'per_page' => $schema->integer()->min(1)->max(2000)->nullable(),
        ];
    }
}
