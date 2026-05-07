<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use LaravelZohoMcp\Exceptions\UserFacingZohoException;
use LaravelZohoMcp\Mcp\ZohoMcpTools;

abstract class AbstractZohoTool extends Tool
{
    public function __construct(
        protected readonly ZohoMcpTools $zohoMcpTools,
    ) {}

    /**
     * @param  callable(): array<string, mixed>  $callback
     */
    protected function jsonResponse(callable $callback): Response
    {
        try {
            return Response::json($callback());
        } catch (UserFacingZohoException $e) {
            return Response::error($e->getMessage());
        }
    }
}
