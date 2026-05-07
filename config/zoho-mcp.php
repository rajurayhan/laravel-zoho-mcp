<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Zoho OAuth (refresh token) — server credentials
    |--------------------------------------------------------------------------
    |
    | Create a self-client or server-based OAuth client in Zoho API Console,
    | generate a refresh token with the scopes your integration needs, then
    | store values in your Laravel environment (never commit secrets).
    |
    */

    'accounts_url' => env('ZOHO_ACCOUNTS_URL', 'https://accounts.zoho.com'),

    'api_base_url' => env('ZOHO_API_BASE_URL', 'https://www.zohoapis.com'),

    'client_id' => env('ZOHO_CLIENT_ID'),

    'client_secret' => env('ZOHO_CLIENT_SECRET'),

    'refresh_token' => env('ZOHO_REFRESH_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | CRM API version segment
    |--------------------------------------------------------------------------
    |
    | Default follows Zoho CRM REST v8. Override if your org uses another path.
    |
    */

    'crm_api_prefix' => env('ZOHO_CRM_API_PREFIX', 'crm/v8'),

    /*
    |--------------------------------------------------------------------------
    | MCP server metadata
    |--------------------------------------------------------------------------
    */

    'server_name' => env('ZOHO_MCP_SERVER_NAME', 'Laravel Zoho MCP'),

    'server_version' => env('ZOHO_MCP_SERVER_VERSION', '1.0.0'),

    'instructions' => env('ZOHO_MCP_INSTRUCTIONS', <<<'TXT'
This server exposes Zoho REST APIs through MCP tools. Prefer CRM-specific tools when working with Zoho CRM.
Use zoho_api_request only when you need an endpoint that has no dedicated tool. Respect Zoho rate limits and scopes granted to the refresh token.
TXT
    ),

];
