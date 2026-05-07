# Laravel Zoho MCP

Laravel package that runs a [Model Context Protocol](https://modelcontextprotocol.io) (MCP) server over stdio and exposes **Zoho REST API** operations as MCP tools (backed by the official [`mcp/sdk`](https://github.com/modelcontextprotocol/php-sdk) PHP SDK).

## Requirements

- PHP 8.2+ (with `pdo_sqlite` recommended for automated tests; production uses your app database)
- Laravel 11 or 12
- A Zoho OAuth **client** (client id + secret) registered in [Zoho API Console](https://api-console.zoho.com/), with redirect URL matching your app

## Installation

```bash
composer require laravel-zoho/mcp-server
```

The service provider **loads migrations automatically**. Run `php artisan migrate` in your app as usual.

Publish configuration (optional):

```bash
php artisan vendor:publish --tag=zoho-mcp-config
```

## Multi-user OAuth (recommended)

Each Laravel user can complete the **Zoho authorization code** flow in the browser. Tokens are stored in `zoho_mcp_oauth_connections` (encrypted refresh and access tokens). The MCP stdio process then runs **as that user** when you pass a short-lived **MCP access token** (stored in `zoho_mcp_access_tokens`).

### 1. Environment

| Variable | Purpose |
|----------|---------|
| `ZOHO_CLIENT_ID` | OAuth client id from Zoho API Console |
| `ZOHO_CLIENT_SECRET` | OAuth client secret |
| `ZOHO_ACCOUNTS_URL` | Accounts host for your data center (default `https://accounts.zoho.com`) |
| `ZOHO_OAUTH_CALLBACK_URL` | **Must match** the redirect URL configured in Zoho (full URL to `/zoho-mcp/oauth/callback` unless you changed `ZOHO_MCP_OAUTH_PREFIX`) |
| `ZOHO_OAUTH_SCOPES` | Comma-separated Zoho scopes (defaults include CRM modules/settings) |
| `APP_URL` | Used when `ZOHO_OAUTH_CALLBACK_URL` is not set, so generated `route()` URLs match Zoho’s registered redirect |

### 2. Web routes (registered by the package)

With default prefix `zoho-mcp` and middleware `web`, `auth`:

1. **Start OAuth:** `GET /zoho-mcp/oauth/authorize` (named `zoho-mcp.oauth.authorize`) — user must be signed in to your Laravel app.
2. **Callback:** `GET /zoho-mcp/oauth/callback` — exchanges `code` for tokens and upserts `zoho_mcp_oauth_connections` for `Auth::id()`.
3. **Create MCP token:** `POST /zoho-mcp/mcp-access-tokens` (JSON body optional: `name`, `expires_in_days`) — returns a **plaintext token once**; store it as `ZOHO_MCP_ACCESS_TOKEN` for Cursor (or pass `--token=` to Artisan).

You can change prefix, middleware, or disable route registration via `config/zoho-mcp.php` (`oauth.*` keys).

### 3. Run the MCP server per user

```bash
export ZOHO_MCP_ACCESS_TOKEN='zmcp_....'   # from step 3
php artisan zoho:mcp
```

Or:

```bash
php artisan zoho:mcp --token='zmcp_....'
```

### Cursor example (multi-user)

```json
{
  "mcpServers": {
    "zoho": {
      "command": "php",
      "args": ["/absolute/path/to/your/project/artisan", "zoho:mcp"],
      "cwd": "/absolute/path/to/your/project",
      "env": {
        "ZOHO_MCP_ACCESS_TOKEN": "zmcp_your_personal_token_here"
      }
    }
  }
}
```

Each teammate uses their **own** `ZOHO_MCP_ACCESS_TOKEN` after signing in to your app and connecting Zoho.

## Legacy single-tenant mode (optional)

If you set `ZOHO_REFRESH_TOKEN` **and** omit `ZOHO_MCP_ACCESS_TOKEN` / `--token`, `zoho:mcp` falls back to one global refresh token from the environment (previous behavior). This is not suitable for multiple humans sharing one token safely.

| Variable | Purpose |
|----------|---------|
| `ZOHO_REFRESH_TOKEN` | Long-lived refresh token (legacy mode only) |
| `ZOHO_API_BASE_URL` | API host when not using per-user `api_domain` from OAuth |
| `ZOHO_CRM_API_PREFIX` | CRM path prefix (default `crm/v8`) |

## MCP tools

| Tool | Description |
|------|-------------|
| `zoho_api_request` | Generic `GET` / `POST` / `PUT` / `PATCH` / `DELETE` under the active user’s Zoho API base URL. |
| `zoho_crm_list_modules` | `GET …/settings/modules` |
| `zoho_crm_get_records` | Paginated list for a module |
| `zoho_crm_get_record` | Single record by id |
| `zoho_crm_create_records` | `POST` with Zoho `data` array |
| `zoho_crm_update_records` | `PUT` with Zoho `data` array (records include `id`) |
| `zoho_crm_delete_records` | `DELETE` with `ids` query string |
| `zoho_crm_coql_query` | Read-only COQL |
| `zoho_crm_search_records` | Module search with a `criteria` expression |

Validation and HTTP errors are surfaced to MCP clients using `ToolCallException` where appropriate so the model can recover.

## Security

- MCP access tokens are **secrets** (like API keys). Rotate by deleting rows or adding expiry (`expires_in_days` when creating tokens).
- Zoho refresh tokens in the database are **encrypted** with your app key; protect `APP_KEY` and database backups.
- Disable package OAuth routes in API-only apps with `ZOHO_MCP_REGISTER_OAUTH_ROUTES=false` if you register equivalent routes yourself.

## License

MIT
