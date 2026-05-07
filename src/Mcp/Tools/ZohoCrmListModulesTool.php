<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('zoho_crm_list_modules')]
#[Description('List Zoho CRM modules available to the authenticated Zoho user.')]
final class ZohoCrmListModulesTool extends AbstractZohoTool
{
    public function handle(Request $_request): Response
    {
        return $this->jsonResponse(fn () => $this->zohoMcpTools->zoho_crm_list_modules());
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
