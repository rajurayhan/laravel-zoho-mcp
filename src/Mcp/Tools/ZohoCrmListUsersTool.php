<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('zoho_crm_list_users')]
#[Description('List CRM users (GET /users). type: AllUsers, ActiveUsers, AdminUsers, CurrentUser, etc.')]
final class ZohoCrmListUsersTool extends AbstractZohoTool
{
    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'type' => ['nullable', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
            'ids' => ['nullable', 'string'],
        ]);

        return $this->jsonResponse(fn () => $this->zohoMcpTools->zoho_crm_list_users(
            $data['type'] ?? null,
            $data['page'] ?? null,
            $data['per_page'] ?? null,
            $data['ids'] ?? null,
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->nullable()->description('e.g. AllUsers, ActiveUsers, AdminUsers, CurrentUser'),
            'page' => $schema->integer()->min(1)->nullable(),
            'per_page' => $schema->integer()->min(1)->max(200)->nullable(),
            'ids' => $schema->string()->nullable()->description('Comma-separated user ids (up to 100)'),
        ];
    }
}
