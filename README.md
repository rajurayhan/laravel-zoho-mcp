# Laravel Zoho MCP

Laravel package that runs a [Model Context Protocol](https://modelcontextprotocol.io) (MCP) server over stdio and exposes **Zoho REST API** operations as MCP tools (backed by the official [`mcp/sdk`](https://github.com/modelcontextprotocol/php-sdk) PHP SDK).

## Requirements

- PHP 8.2+
- Laravel 11 or 12
- A Zoho OAuth client with a **refresh token** and API scopes you intend to call (for example Zoho CRM scopes if you use the CRM tools)

## Installation

```bash
composer require laravel-zoho/mcp-server
```

Publish configuration (optional):

```bash
php artisan vendor:publish --tag=zoho-mcp-config
```

Set environment variables (see `config/zoho-mcp.php`):

| Variable | Purpose |
|----------|---------|
| `ZOHO_CLIENT_ID` | OAuth client id from Zoho API Console |
| `ZOHO_CLIENT_SECRET` | OAuth client secret |
| `ZOHO_REFRESH_TOKEN` | Long-lived refresh token |
| `ZOHO_ACCOUNTS_URL` | Accounts host for your data center (default `https://accounts.zoho.com`) |
| `ZOHO_API_BASE_URL` | API host (default `https://www.zohoapis.com`; use `https://www.zohoapis.eu` for EU, and so on) |
| `ZOHO_CRM_API_PREFIX` | CRM path prefix (default `crm/v8`) |

## Running the MCP server

From your Laravel application root:

```bash
php artisan zoho:mcp
```

The process speaks MCP over **stdio** (JSON-RPC), which matches how editors such as Cursor wire local MCP servers.

### Cursor example

Add a server entry that runs your app’s Artisan command (adjust paths):

```json
{
  "mcpServers": {
    "zoho": {
      "command": "php",
      "args": ["/absolute/path/to/your/project/artisan", "zoho:mcp"],
      "cwd": "/absolute/path/to/your/project"
    }
  }
}
```

## MCP tools

| Tool | Description |
|------|-------------|
| `zoho_api_request` | Generic `GET` / `POST` / `PUT` / `PATCH` / `DELETE` against paths under your configured `ZOHO_API_BASE_URL`. |
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

- Treat this server like **root access to whatever scopes the refresh token holds**. Run it only in trusted environments.
- Never commit OAuth secrets; use environment variables or your platform’s secret store.

## License

MIT
