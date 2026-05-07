<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('zoho_crm_get_related_records')]
#[Description('Get records from a related list (e.g. Notes on a Lead). fields is required (comma-separated API names). See Zoho Get Related Records API.')]
final class ZohoCrmGetRelatedRecordsTool extends AbstractZohoTool
{
    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'module_api_name' => ['required', 'string'],
            'record_id' => ['required', 'string'],
            'related_list_api_name' => ['required', 'string'],
            'fields' => ['required', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
            'sort_by' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'string', 'in:asc,desc,ASC,DESC'],
        ]);

        return $this->jsonResponse(fn () => $this->zohoMcpTools->zoho_crm_get_related_records(
            $data['module_api_name'],
            $data['record_id'],
            $data['related_list_api_name'],
            $data['fields'],
            $data['page'] ?? null,
            $data['per_page'] ?? null,
            $data['sort_by'] ?? null,
            isset($data['sort_order']) ? strtolower((string) $data['sort_order']) : null,
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'module_api_name' => $schema->string()->required(),
            'record_id' => $schema->string()->required(),
            'related_list_api_name' => $schema->string()->required(),
            'fields' => $schema->string()->required()->description('Comma-separated field API names'),
            'page' => $schema->integer()->min(1)->nullable(),
            'per_page' => $schema->integer()->min(1)->max(200)->nullable(),
            'sort_by' => $schema->string()->nullable(),
            'sort_order' => $schema->string()->enum(['asc', 'desc', 'ASC', 'DESC'])->nullable(),
        ];
    }
}
