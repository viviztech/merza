<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    public const ABILITY_ALL = '*';
    public const ABILITY_CATALOG_READ = 'catalog:read';
    public const ABILITY_ORDERS_READ = 'orders:read';
    public const ABILITY_ORDERS_WRITE = 'orders:write';

    public static function availableAbilities(): array
    {
        return [
            self::ABILITY_CATALOG_READ => 'Read product catalog and categories',
            self::ABILITY_ORDERS_READ  => 'Read orders (read permission)',
            self::ABILITY_ORDERS_WRITE => 'Write orders (create & update permission)',
            self::ABILITY_ALL          => 'All current and future API access',
        ];
    }

    protected $fillable = [
        'created_by',
        'name',
        'key_prefix',
        'key_hash',
        'abilities',
        'last_used_at',
        'expires_at',
        'revoked_at',
    ];

    protected $hidden = [
        'key_hash',
    ];

    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function generateSecret(): string
    {
        return 'merza_live_' . bin2hex(random_bytes(32));
    }

    public static function hashSecret(string $secret): string
    {
        return hash('sha256', $secret);
    }

    public static function prefixFor(string $secret): string
    {
        return substr($secret, 0, 20);
    }

    public function isActive(): bool
    {
        return is_null($this->revoked_at)
            && (is_null($this->expires_at) || $this->expires_at->isFuture());
    }

    public function allows(string $ability): bool
    {
        $abilities = $this->abilities ?? [];

        return in_array('*', $abilities, true) || in_array($ability, $abilities, true);
    }
}
