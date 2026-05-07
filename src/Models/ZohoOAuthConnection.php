<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $refresh_token
 * @property string|null $access_token
 * @property \Illuminate\Support\Carbon|null $access_token_expires_at
 * @property string $accounts_url
 * @property string $api_base_url
 * @property string|null $scope
 */
final class ZohoOAuthConnection extends Model
{
    protected $table = 'zoho_mcp_oauth_connections';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'refresh_token',
        'access_token',
        'access_token_expires_at',
        'accounts_url',
        'api_base_url',
        'scope',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'refresh_token' => 'encrypted',
            'access_token' => 'encrypted',
            'access_token_expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<object, self>
     */
    public function user(): BelongsTo
    {
        $model = config('zoho-mcp.oauth.user_model') ?? config('auth.providers.users.model');
        if (! is_string($model) || ! class_exists($model)) {
            throw new \RuntimeException('Configure auth.providers.users.model or zoho-mcp.oauth.user_model.');
        }

        return $this->belongsTo($model, 'user_id');
    }
}
