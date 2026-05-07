<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('zoho_crm_list_profiles')]
#[Description('List CRM profiles (GET /settings/profiles).')]
final class ZohoCrmListProfilesTool extends AbstractZohoTool
{
    public function handle(Request $_request): Response
    {
        return $this->jsonResponse(fn () => $this->zohoMcpTools->zoho_crm_list_profiles());
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
