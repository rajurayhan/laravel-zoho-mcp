<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $name
 * @property string $token
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $last_used_at
 */
final class ZohoMcpAccessToken extends Model
{
    protected $table = 'zoho_mcp_access_tokens';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'token',
        'expires_at',
        'last_used_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_used_at' => 'datetime',
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

    public static function hashPlainToken(string $plain): string
    {
        return hash('sha256', $plain);
    }

    /**
     * @return array{0: string, 1: self} Plain token (show once) and persisted row.
     */
    public static function createPlainTokenForUser(int|string $userId, ?string $name = null, ?\DateTimeInterface $expiresAt = null): array
    {
        $plain = 'zmcp_'.Str::lower(Str::random(48));

        $row = static::query()->create([
            'user_id' => $userId,
            'name' => $name,
            'token' => static::hashPlainToken($plain),
            'expires_at' => $expiresAt,
        ]);

        return [$plain, $row];
    }

    public static function findValidFromPlain(string $plain): ?self
    {
        $hash = static::hashPlainToken($plain);

        return static::query()
            ->where('token', $hash)
            ->where(function ($q): void {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
    }
}
