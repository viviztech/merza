<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppSession extends Model
{
    protected $table = 'whatsapp_sessions';

    protected $fillable = ['phone', 'state', 'data', 'expires_at'];

    protected $casts = [
        'data'       => 'array',
        'expires_at' => 'datetime',
    ];

    // 90 min, not 30 — a customer stepping away to check a price with someone
    // shouldn't lose their cart over a short gap. See resumePromptOrReset().
    private const SESSION_TTL_MINUTES = 90;

    public static function getOrCreate(string $phone): self
    {
        $session = static::firstOrCreate(
            ['phone' => $phone],
            [
                'state'      => 'start',
                'data'       => [],
                'expires_at' => now()->addMinutes(self::SESSION_TTL_MINUTES),
            ]
        );

        if (! $session->wasRecentlyCreated && $session->isExpired()) {
            $session->resumePromptOrReset();
        }

        return $session;
    }

    /**
     * An expired session used to be deleted outright — cart, delivery zone, and
     * any half-typed checkout draft all silently gone. Now: if there was
     * something worth resuming, stash it and ask; otherwise reset cleanly.
     * The stash lives in `state: resume_prompt`, read back by
     * WhatsAppFlowService's resume_cart/fresh_start button handlers.
     */
    public function resumePromptOrReset(): void
    {
        $cart = $this->data['cart'] ?? [];

        if ($this->state === 'resume_prompt' || empty($cart)) {
            $this->update([
                'state'      => 'start',
                'data'       => [],
                'expires_at' => now()->addMinutes(self::SESSION_TTL_MINUTES),
            ]);
            return;
        }

        $this->update([
            'state'      => 'resume_prompt',
            'data'       => array_merge($this->data, ['stashed_state' => $this->state]),
            'expires_at' => now()->addMinutes(self::SESSION_TTL_MINUTES),
        ]);
    }

    public function touch30(): void
    {
        $this->update(['expires_at' => now()->addMinutes(self::SESSION_TTL_MINUTES)]);
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
            'expires_at' => now()->addMinutes(self::SESSION_TTL_MINUTES),
        ]);
    }
}
