<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('zoho_crm_composite_requests')]
#[Description('POST Zoho CRM composite API (up to 5 sub-requests in one round trip). Body must include __composite_requests array; optional rollback_on_fail and parallel_execution. See Zoho composite API docs.')]
final class ZohoCrmCompositeRequestsTool extends AbstractZohoTool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            '__composite_requests' => ['required', 'array', 'min:1', 'max:5'],
            'rollback_on_fail' => ['sometimes', 'boolean'],
            'parallel_execution' => ['sometimes', 'boolean'],
        ]);

        return $this->jsonResponse(fn () => $this->zohoMcpTools->zoho_crm_composite_requests($validated));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            '__composite_requests' => $schema->array()->min(1)->max(5)->required()->description('Up to 5 sub-request objects per Zoho composite API'),
            'rollback_on_fail' => $schema->boolean()->nullable(),
            'parallel_execution' => $schema->boolean()->nullable(),
        ];
    }
}
