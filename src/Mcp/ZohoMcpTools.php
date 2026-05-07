<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Mcp;

use LaravelZohoMcp\Zoho\ZohoApiClient;
use Mcp\Exception\ToolCallException;

/**
 * MCP tool handlers for Zoho REST APIs. Resolved from the Laravel container
 * so ZohoApiClient receives your application configuration.
 */
final class ZohoMcpTools
{
    public function __construct(
        private readonly ZohoApiClient $zoho,
    ) {}

    /**
     * Low-level Zoho API call against the configured API base URL (path is relative to that host).
     *
     * @param  array<string, mixed>|null  $query
     * @param  array<string, mixed>|null  $body
     * @return array<string, mixed>
     */
    public function zoho_api_request(
        string $method,
        string $path,
        ?array $query = null,
        ?array $body = null,
    ): array {
        $allowed = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
        $m = strtoupper(trim($method));
        if (! in_array($m, $allowed, true)) {
            throw new ToolCallException('method must be one of: '.implode(', ', $allowed));
        }

        return $this->zoho->request($m, $path, $query ?? [], $body);
    }

    /**
     * List CRM modules available to the authenticated user (Zoho CRM settings/modules).
     *
     * @return array<string, mixed>
     */
    public function zoho_crm_list_modules(): array
    {
        $p = $this->zoho->crmPrefix();

        return $this->zoho->request('GET', $p.'/settings/modules');
    }

    /**
     * List CRM records for a module with optional pagination and field projection.
     *
     * @return array<string, mixed>
     */
    public function zoho_crm_get_records(
        string $module_api_name,
        ?int $page = null,
        ?int $per_page = null,
        ?string $fields = null,
    ): array {
        $query = [];
        if ($page !== null) {
            $query['page'] = $page;
        }
        if ($per_page !== null) {
            $query['per_page'] = min(200, max(1, $per_page));
        }
        if ($fields !== null && $fields !== '') {
            $query['fields'] = $fields;
        }
        $p = $this->zoho->crmPrefix();

        return $this->zoho->request('GET', $p.'/'.$this->moduleSegment($module_api_name), $query);
    }

    /**
     * Fetch a single CRM record by id.
     *
     * @return array<string, mixed>
     */
    public function zoho_crm_get_record(
        string $module_api_name,
        string $record_id,
        ?string $fields = null,
    ): array {
        $query = [];
        if ($fields !== null && $fields !== '') {
            $query['fields'] = $fields;
        }
        $p = $this->zoho->crmPrefix();

        return $this->zoho->request('GET', $p.'/'.$this->moduleSegment($module_api_name).'/'.$record_id, $query);
    }

    /**
     * Create one or more CRM records. Each item in records becomes a Zoho "data" row (API shape).
     *
     * @param  list<array<string, mixed>>  $records
     * @return array<string, mixed>
     */
    public function zoho_crm_create_records(string $module_api_name, array $records): array
    {
        if ($records === []) {
            throw new ToolCallException('records must contain at least one object.');
        }
        $p = $this->zoho->crmPrefix();

        return $this->zoho->request('POST', $p.'/'.$this->moduleSegment($module_api_name), [], ['data' => $records]);
    }

    /**
     * Update existing CRM records (each object must include the Zoho record id).
     *
     * @param  list<array<string, mixed>>  $records
     * @return array<string, mixed>
     */
    public function zoho_crm_update_records(string $module_api_name, array $records): array
    {
        if ($records === []) {
            throw new ToolCallException('records must contain at least one object with an id field.');
        }
        $p = $this->zoho->crmPrefix();

        return $this->zoho->request('PUT', $p.'/'.$this->moduleSegment($module_api_name), [], ['data' => $records]);
    }

    /**
     * Delete CRM records by id (comma-separated list is sent to Zoho as the ids query parameter).
     *
     * @param  list<string>  $record_ids
     * @return array<string, mixed>
     */
    public function zoho_crm_delete_records(string $module_api_name, array $record_ids): array
    {
        if ($record_ids === []) {
            throw new ToolCallException('record_ids must contain at least one id.');
        }
        $ids = implode(',', array_map(static fn (string $id): string => trim($id), $record_ids));
        if ($ids === '') {
            throw new ToolCallException('record_ids must be non-empty strings.');
        }
        $p = $this->zoho->crmPrefix();

        return $this->zoho->request('DELETE', $p.'/'.$this->moduleSegment($module_api_name), ['ids' => $ids]);
    }

    /**
     * Run a read-only COQL query against Zoho CRM.
     *
     * @return array<string, mixed>
     */
    public function zoho_crm_coql_query(string $select_query): array
    {
        $q = trim($select_query);
        if ($q === '') {
            throw new ToolCallException('select_query must be a non-empty COQL string.');
        }
        $p = $this->zoho->crmPrefix();

        return $this->zoho->request('POST', $p.'/coql', [], ['select_query' => $q]);
    }

    /**
     * Search CRM records using Zoho criteria syntax in the criteria query parameter.
     *
     * @return array<string, mixed>
     */
    public function zoho_crm_search_records(
        string $module_api_name,
        string $criteria,
        ?int $page = null,
        ?int $per_page = null,
    ): array {
        $crit = trim($criteria);
        if ($crit === '') {
            throw new ToolCallException('criteria must be a non-empty Zoho criteria expression.');
        }
        $query = ['criteria' => $crit];
        if ($page !== null) {
            $query['page'] = $page;
        }
        if ($per_page !== null) {
            $query['per_page'] = min(200, max(1, $per_page));
        }
        $p = $this->zoho->crmPrefix();

        return $this->zoho->request('GET', $p.'/'.$this->moduleSegment($module_api_name).'/search', $query);
    }

    private function moduleSegment(string $module_api_name): string
    {
        $m = trim($module_api_name);
        if ($m === '' || ! preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $m)) {
            throw new ToolCallException('module_api_name must be a non-empty Zoho API module identifier.');
        }

        return $m;
    }
}
