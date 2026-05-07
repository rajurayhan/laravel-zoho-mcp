<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Mcp;

use LaravelZohoMcp\Zoho\ZohoApiClient;
use LaravelZohoMcp\Exceptions\UserFacingZohoException;

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
            throw new UserFacingZohoException('method must be one of: '.implode(', ', $allowed));
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
            throw new UserFacingZohoException('records must contain at least one object.');
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
            throw new UserFacingZohoException('records must contain at least one object with an id field.');
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
            throw new UserFacingZohoException('record_ids must contain at least one id.');
        }
        $ids = implode(',', array_map(static fn (string $id): string => trim($id), $record_ids));
        if ($ids === '') {
            throw new UserFacingZohoException('record_ids must be non-empty strings.');
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
            throw new UserFacingZohoException('select_query must be a non-empty COQL string.');
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
            throw new UserFacingZohoException('criteria must be a non-empty Zoho criteria expression.');
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

    /**
     * @return array<string, mixed>
     */
    public function zoho_crm_get_organization(): array
    {
        $p = $this->zoho->crmPrefix();

        return $this->zoho->request('GET', $p.'/org');
    }

    /**
     * Full module metadata (fields, layouts, related lists) for one module.
     *
     * @see https://www.zoho.com/crm/developer/docs/api/v8/module-meta.html
     *
     * @return array<string, mixed>
     */
    public function zoho_crm_get_module_metadata(string $module_api_name): array
    {
        $p = $this->zoho->crmPrefix();

        return $this->zoho->request('GET', $p.'/settings/modules/'.$this->moduleSegment($module_api_name));
    }

    /**
     * Field metadata for a module (lighter than full module metadata).
     *
     * @see https://www.zoho.com/crm/developer/docs/api/v8/module-meta.html
     *
     * @return array<string, mixed>
     */
    public function zoho_crm_get_fields(string $module_api_name, ?string $layout_id = null): array
    {
        $query = ['module' => $this->moduleSegment($module_api_name)];
        if ($layout_id !== null && $layout_id !== '') {
            $query['layout_id'] = $layout_id;
        }
        $p = $this->zoho->crmPrefix();

        return $this->zoho->request('GET', $p.'/settings/fields', $query);
    }

    /**
     * Layout definitions for a module.
     *
     * @return array<string, mixed>
     */
    public function zoho_crm_get_layouts(string $module_api_name): array
    {
        $p = $this->zoho->crmPrefix();

        return $this->zoho->request('GET', $p.'/settings/layouts', [
            'module' => $this->moduleSegment($module_api_name),
        ]);
    }

    /**
     * Related-list metadata (api names, hrefs) for a module.
     *
     * @see https://www.zoho.com/crm/developer/docs/api/v8/related-list-meta.html
     *
     * @return array<string, mixed>
     */
    public function zoho_crm_get_related_lists_metadata(string $module_api_name, ?string $layout_id = null): array
    {
        $query = ['module' => $this->moduleSegment($module_api_name)];
        if ($layout_id !== null && $layout_id !== '') {
            $query['layout_id'] = $layout_id;
        }
        $p = $this->zoho->crmPrefix();

        return $this->zoho->request('GET', $p.'/settings/related_lists', $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function zoho_crm_list_users(
        ?string $type = null,
        ?int $page = null,
        ?int $per_page = null,
        ?string $ids = null,
    ): array {
        $query = [];
        if ($type !== null && $type !== '') {
            $query['type'] = $type;
        }
        if ($page !== null) {
            $query['page'] = $page;
        }
        if ($per_page !== null) {
            $query['per_page'] = min(200, max(1, $per_page));
        }
        if ($ids !== null && $ids !== '') {
            $query['ids'] = $ids;
        }
        $p = $this->zoho->crmPrefix();

        return $this->zoho->request('GET', $p.'/users', $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function zoho_crm_get_user(string $user_id): array
    {
        $p = $this->zoho->crmPrefix();

        return $this->zoho->request('GET', $p.'/users/'.$this->zohoRecordId($user_id, 'user'));
    }

    /**
     * @return array<string, mixed>
     */
    public function zoho_crm_list_roles(): array
    {
        $p = $this->zoho->crmPrefix();

        return $this->zoho->request('GET', $p.'/settings/roles');
    }

    /**
     * @return array<string, mixed>
     */
    public function zoho_crm_get_role(string $role_id): array
    {
        $p = $this->zoho->crmPrefix();

        return $this->zoho->request('GET', $p.'/settings/roles/'.$this->zohoRecordId($role_id, 'role'));
    }

    /**
     * @return array<string, mixed>
     */
    public function zoho_crm_list_profiles(): array
    {
        $p = $this->zoho->crmPrefix();

        return $this->zoho->request('GET', $p.'/settings/profiles');
    }

    /**
     * @return array<string, mixed>
     */
    public function zoho_crm_get_profile(string $profile_id): array
    {
        $p = $this->zoho->crmPrefix();

        return $this->zoho->request('GET', $p.'/settings/profiles/'.$this->zohoRecordId($profile_id, 'profile'));
    }

    /**
     * @return array<string, mixed>
     */
    public function zoho_crm_list_territories(?int $page = null, ?int $per_page = null): array
    {
        $query = [];
        if ($page !== null) {
            $query['page'] = $page;
        }
        if ($per_page !== null) {
            $query['per_page'] = min(2000, max(1, $per_page));
        }
        $p = $this->zoho->crmPrefix();

        return $this->zoho->request('GET', $p.'/settings/territories', $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function zoho_crm_get_territory(string $territory_id): array
    {
        $p = $this->zoho->crmPrefix();

        return $this->zoho->request('GET', $p.'/settings/territories/'.$this->zohoRecordId($territory_id, 'territory'));
    }

    /**
     * Records from a related list (Notes, Contacts on Account, etc.).
     *
     * @see https://www.zoho.com/crm/developer/docs/api/v8/get-related-records.html
     *
     * @return array<string, mixed>
     */
    public function zoho_crm_get_related_records(
        string $module_api_name,
        string $record_id,
        string $related_list_api_name,
        string $fields,
        ?int $page = null,
        ?int $per_page = null,
        ?string $sort_by = null,
        ?string $sort_order = null,
    ): array {
        $f = trim($fields);
        if ($f === '') {
            throw new UserFacingZohoException('fields is required by Zoho (comma-separated API field names).');
        }
        $query = ['fields' => $f];
        if ($page !== null) {
            $query['page'] = $page;
        }
        if ($per_page !== null) {
            $query['per_page'] = min(200, max(1, $per_page));
        }
        if ($sort_by !== null && $sort_by !== '') {
            $query['sort_by'] = $sort_by;
        }
        if ($sort_order !== null && $sort_order !== '') {
            $query['sort_order'] = $sort_order;
        }
        $p = $this->zoho->crmPrefix();
        $path = $p.'/'.$this->moduleSegment($module_api_name).'/'
            .$this->zohoRecordId($record_id, 'record').'/'
            .$this->relatedListSegment($related_list_api_name);

        return $this->zoho->request('GET', $path, $query);
    }

    /**
     * Run up to five CRM API sub-requests in one call (composite).
     *
     * @see https://www.zoho.com/crm/developer/docs/api/v8/composite-api.html
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function zoho_crm_composite_requests(array $payload): array
    {
        if (! isset($payload['__composite_requests']) || ! is_array($payload['__composite_requests'])) {
            throw new UserFacingZohoException('payload must include a __composite_requests array.');
        }
        $subs = $payload['__composite_requests'];
        if ($subs === []) {
            throw new UserFacingZohoException('__composite_requests must contain at least one sub-request.');
        }
        if (count($subs) > 5) {
            throw new UserFacingZohoException('Zoho composite API allows at most 5 sub-requests.');
        }
        $p = $this->zoho->crmPrefix();

        return $this->zoho->request('POST', $p.'/__composite_requests', [], $payload);
    }

    private function moduleSegment(string $module_api_name): string
    {
        $m = trim($module_api_name);
        if ($m === '' || ! preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $m)) {
            throw new UserFacingZohoException('module_api_name must be a non-empty Zoho API module identifier.');
        }

        return $m;
    }

    private function relatedListSegment(string $api_name): string
    {
        $s = trim($api_name);
        if ($s === '' || ! preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $s)) {
            throw new UserFacingZohoException('related_list_api_name must be a valid Zoho related list API name.');
        }

        return $s;
    }

    private function zohoRecordId(string $id, string $label): string
    {
        $id = trim($id);
        if ($id === '' || ! preg_match('/^[0-9]+$/', $id)) {
            throw new UserFacingZohoException("Invalid {$label} id (expected numeric Zoho id).");
        }

        return $id;
    }
}
