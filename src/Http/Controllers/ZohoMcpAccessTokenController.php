<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use LaravelZohoMcp\Models\ZohoMcpAccessToken;

final class ZohoMcpAccessTokenController
{
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ]);

        $expiresAt = isset($validated['expires_in_days'])
            ? now()->addDays((int) $validated['expires_in_days'])
            : null;

        [$plain, $row] = ZohoMcpAccessToken::createPlainTokenForUser(
            $user->getAuthIdentifier(),
            $validated['name'] ?? null,
            $expiresAt,
        );

        return response()->json([
            'token' => $plain,
            'token_id' => $row->id,
            'message' => 'Store this token in ZOHO_MCP_ACCESS_TOKEN (or pass php artisan zoho:mcp --token=...). It is shown only once.',
        ], 201);
    }
}
