<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Conversation;
use Illuminate\Support\Carbon;

class WhatsAppMessagePolicy
{
    public function lastInboundAt(Contact $contact): ?Carbon
    {
        $message = Conversation::query()
            ->where('contact_id', $contact->id)
            ->where('channel', 'whatsapp')
            ->where('direction', 'inbound')
            ->orderByRaw('COALESCE(sent_at, created_at) DESC')
            ->first(['sent_at', 'created_at']);

        return $message?->sent_at ?? $message?->created_at;
    }

    public function freeformExpiresAt(Contact $contact): ?Carbon
    {
        return $this->lastInboundAt($contact)?->copy()->addDay();
    }

    public function canSendFreeform(Contact $contact): bool
    {
        $expiresAt = $this->freeformExpiresAt($contact);

        return ! $contact->is_blocked
            && ! $contact->wa_opted_out
            && $expiresAt !== null
            && $expiresAt->isFuture();
    }

    public function canSendFreeformToPhone(string $phone): bool
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (strlen($digits) < 10) {
            return false;
        }

        $last10 = substr($digits, -10);
        $contact = Contact::query()
            ->where('phone', 'like', '%'.substr($last10, -4))
            ->get()
            ->first(fn (Contact $candidate) => substr(preg_replace('/\D/', '', $candidate->phone) ?? '', -10) === $last10);

        return $contact !== null && $this->canSendFreeform($contact);
    }

    public function hasOutreachConsent(Contact $contact, string $category): bool
    {
        return ! $contact->is_blocked
            && ! $contact->wa_opted_out
            && $contact->whatsAppConsents()
                ->where('category', $category)
                ->whereNull('revoked_at')
                ->exists();
    }
}
