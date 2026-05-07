<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('zoho_crm_get_user')]
#[Description('Get a single CRM user by id (GET /users/{id}).')]
final class ZohoCrmGetUserTool extends AbstractZohoTool
{
    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'user_id' => ['required', 'string'],
        ]);

        return $this->jsonResponse(fn () => $this->zohoMcpTools->zoho_crm_get_user($data['user_id']));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'user_id' => $schema->string()->required()->description('Numeric Zoho user id'),
        ];
    }
}
