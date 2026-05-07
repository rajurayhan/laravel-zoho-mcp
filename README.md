# Laravel Zoho MCP

Laravel package that exposes **Zoho REST APIs** to AI clients through the [Model Context Protocol](https://modelcontextprotocol.io), using Laravel’s first-party **[Laravel MCP](https://laravel.com/docs/mcp)** package (`laravel/mcp`) — not a separate low-level MCP SDK.

## Requirements

- **PHP 8.3+**
- **Laravel 13+** (this release aligns with `laravel/mcp` and Illuminate 13 components)
- A Zoho OAuth **client** (client id + secret) in [Zoho API Console](https://api-console.zoho.com/)

`laravel/mcp` is a **direct dependency**; installing this package pulls it in and registers the MCP console commands (for example `mcp:start`).

## Installation

```bash
composer require laravel-zoho/mcp-server
```

The service provider **loads migrations automatically**. Run `php artisan migrate` in your app.

Publish configuration (optional):

```bash
php artisan vendor:publish --tag=zoho-mcp-config
```

## Multi-user OAuth (recommended)

Each Laravel user completes the **Zoho authorization code** flow in the browser. Tokens are stored in `zoho_mcp_oauth_connections` (encrypted). Each user then gets an **MCP access token** (`zmcp_…`) from `POST /zoho-mcp/mcp-access-tokens` to pass as `ZOHO_MCP_ACCESS_TOKEN`.

See `config/zoho-mcp.php` for `ZOHO_OAUTH_CALLBACK_URL`, scopes, route prefix, and middleware.

## Running the MCP server (local / stdio)

This package registers a **local** MCP server handle (default **`zoho`**, overridable with `ZOHO_MCP_LOCAL_HANDLE` / `config('zoho-mcp.mcp_local_handle')`):

```bash
php artisan mcp:start zoho
```

Compatibility wrapper (sets `--token` for this process, then calls `mcp:start`):

```bash
php artisan zoho:mcp --token='zmcp_....'
# or
export ZOHO_MCP_ACCESS_TOKEN='zmcp_....'
php artisan zoho:mcp
```

Legacy single-tenant mode still works when `ZOHO_REFRESH_TOKEN` and client credentials are set and no MCP access token is provided (see `BootstrapZohoCredentials`).

### Cursor example

```json
{
  "mcpServers": {
    "zoho": {
      "command": "php",
      "args": ["/absolute/path/to/your/project/artisan", "mcp:start", "zoho"],
      "cwd": "/absolute/path/to/your/project",
      "env": {
        "ZOHO_MCP_ACCESS_TOKEN": "zmcp_your_personal_token_here"
      }
    }
  }
}
```

You may use `zoho:mcp` instead of `mcp:start zoho` if you prefer the wrapper command.

## MCP tools

Tools are implemented as `Laravel\Mcp\Server\Tool` classes and return `Laravel\Mcp\Response::json()` (or `Response::error()` for recoverable issues).

| Tool | Description |
|------|-------------|
| `zoho_api_request` | Generic `GET` / `POST` / `PUT` / `PATCH` / `DELETE` under the active API base URL. |
| `zoho_crm_list_modules` | CRM settings/modules |
| `zoho_crm_get_records` | Paginated list |
| `zoho_crm_get_record` | Single record |
| `zoho_crm_create_records` | Create rows (`data` array) |
| `zoho_crm_update_records` | Update rows (include `id`) |
| `zoho_crm_delete_records` | Delete by ids |
| `zoho_crm_coql_query` | Read-only COQL |
| `zoho_crm_search_records` | Criteria-based search |

## Security

- MCP access tokens are secrets; rotate via expiry or DB deletion.
- Zoho refresh tokens in the database are encrypted with your app key.

## License

MIT
