<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('zoho_crm_get_organization')]
#[Description('Get Zoho CRM organization details (GET /org). Requires ZohoCRM.org scope.')]
final class ZohoCrmGetOrganizationTool extends AbstractZohoTool
{
    public function handle(Request $request): Response
    {
        return $this->jsonResponse(fn () => $this->zohoMcpTools->zoho_crm_get_organization());
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
