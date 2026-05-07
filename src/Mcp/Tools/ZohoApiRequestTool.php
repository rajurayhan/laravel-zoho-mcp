<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('zoho_api_request')]
#[Description('Low-level Zoho REST call. Path is relative to the active API base URL.')]
final class ZohoApiRequestTool extends AbstractZohoTool
{
    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'method' => ['required', 'string', 'in:GET,POST,PUT,PATCH,DELETE'],
            'path' => ['required', 'string'],
            'query' => ['nullable', 'array'],
            'body' => ['nullable', 'array'],
        ]);

        return $this->jsonResponse(fn () => $this->zohoMcpTools->zoho_api_request(
            $data['method'],
            $data['path'],
            $data['query'] ?? null,
            $data['body'] ?? null,
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'method' => $schema->string()->enum(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])->required()->description('HTTP method'),
            'path' => $schema->string()->required()->description('Path relative to the Zoho API base URL'),
            'query' => $schema->object()->nullable()->description('Optional query parameters'),
            'body' => $schema->object()->nullable()->description('Optional JSON body'),
        ];
    }
}
