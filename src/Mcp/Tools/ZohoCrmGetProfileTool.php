<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('zoho_crm_get_profile')]
#[Description('Get one CRM profile including permission details (GET /settings/profiles/{id}).')]
final class ZohoCrmGetProfileTool extends AbstractZohoTool
{
    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'profile_id' => ['required', 'string'],
        ]);

        return $this->jsonResponse(fn () => $this->zohoMcpTools->zoho_crm_get_profile($data['profile_id']));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'profile_id' => $schema->string()->required(),
        ];
    }
}
