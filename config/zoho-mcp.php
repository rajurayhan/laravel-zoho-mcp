<?php

return [

    'accounts_url' => env('ZOHO_ACCOUNTS_URL', 'https://accounts.zoho.com'),

    'api_base_url' => env('ZOHO_API_BASE_URL', 'https://www.zohoapis.com'),

    'client_id' => env('ZOHO_CLIENT_ID'),

    'client_secret' => env('ZOHO_CLIENT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Legacy single-tenant credentials (optional)
    |--------------------------------------------------------------------------
    |
    | When set, php artisan zoho:mcp can run without ZOHO_MCP_ACCESS_TOKEN by
    | using this refresh token for every MCP session (one global Zoho user).
    |
    */

    'refresh_token' => env('ZOHO_REFRESH_TOKEN'),

    'crm_api_prefix' => env('ZOHO_CRM_API_PREFIX', 'crm/v8'),

    'server_name' => env('ZOHO_MCP_SERVER_NAME', 'Laravel Zoho MCP'),

    'server_version' => env('ZOHO_MCP_SERVER_VERSION', '1.0.0'),

    /*
    |--------------------------------------------------------------------------
    | Laravel MCP local server handle
    |--------------------------------------------------------------------------
    |
    | Registered with Laravel\Mcp\Facades\Mcp::local(). Use:
    | php artisan mcp:start {handle}
    |
    */

    'mcp_local_handle' => env('ZOHO_MCP_LOCAL_HANDLE', 'zoho'),

    'instructions' => env('ZOHO_MCP_INSTRUCTIONS', <<<'TXT'
This server exposes Zoho REST APIs through MCP tools. Each MCP session is tied to one Laravel user via ZOHO_MCP_ACCESS_TOKEN.
Prefer CRM-specific tools when working with Zoho CRM. Use zoho_api_request only for endpoints without a dedicated tool.
TXT
    ),

    /*
    |--------------------------------------------------------------------------
    | OAuth (authorization code) for multi-user connections
    |--------------------------------------------------------------------------
    */

    'oauth' => [

        'register_routes' => env('ZOHO_MCP_REGISTER_OAUTH_ROUTES', true),

        'route_prefix' => env('ZOHO_MCP_OAUTH_PREFIX', 'zoho-mcp'),

        'middleware' => ['web', 'auth'],

        'session_state_key' => 'zoho_mcp_oauth_state',

        'callback_route_name' => 'zoho-mcp.oauth.callback',

        /*
        | Explicit redirect URL registered in Zoho API Console. If null, the
        | named route zoho-mcp.oauth.callback is used (requires APP_URL).
        */

        'callback_url' => env('ZOHO_OAUTH_CALLBACK_URL'),

        'completion_redirect' => env('ZOHO_OAUTH_COMPLETION_REDIRECT', '/'),

        'prompt_consent' => env('ZOHO_OAUTH_PROMPT_CONSENT', false),

        /*
        | Comma-separated Zoho scopes (Zoho expects commas in the authorize URL).
        */

        'scopes' => env('ZOHO_OAUTH_SCOPES', 'ZohoCRM.modules.ALL,ZohoCRM.users.ALL,ZohoCRM.settings.ALL'),

        'user_model' => env('ZOHO_MCP_USER_MODEL'),

    ],

];
