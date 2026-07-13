<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppSession extends Model
{
    protected $fillable = ['phone', 'state', 'data', 'expires_at'];

    protected $casts = [
        'data'       => 'array',
        'expires_at' => 'datetime',
    ];

    public static function getOrCreate(string $phone): self
    {
        // Clean up expired sessions for this phone
        static::where('phone', $phone)
            ->where('expires_at', '<', now())
            ->delete();

        return static::firstOrCreate(
            ['phone' => $phone],
            [
                'state'      => 'start',
                'data'       => [],
                'expires_at' => now()->addMinutes(30),
            ]
        );
    }

    public function touch30(): void
    {
        $this->update(['expires_at' => now()->addMinutes(30)]);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function setState(string $state, array $data = []): void
    {
        $this->update([
            'state'      => $state,
            'data'       => $data,
            'expires_at' => now()->addMinutes(30),
        ]);
    }
}
