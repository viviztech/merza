<?php

namespace App\Console\Commands;

use App\Models\BotActivityLog;
use App\Models\BotSetting;
use App\Models\Contact;
use App\Models\Order;
use App\Models\WhatsAppSession;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class NudgeStalledCheckouts extends Command
{
    protected $signature = 'whatsapp:nudge-stalled-checkouts';

    protected $description = 'Send one reminder each to WhatsApp carts stalled mid-checkout, and follow up on orders left unpaid';

    // Below this, a customer is just still shopping — not stalled yet.
    private const STALL_MINUTES = 10;

    // Above this, chasing them is more likely to annoy than convert — leave it to staff.
    private const GIVE_UP_HOURS = 6;

    private const UNPAID_FOLLOWUP_HOURS = 2;
    private const UNPAID_GIVE_UP_DAYS   = 2;

    public function handle(): int
    {
        $settings = BotSetting::current();

        if (! $settings->wa_bot_enabled) {
            $this->info('WhatsApp bot is disabled — skipping.');
            return self::SUCCESS;
        }

        $waService = new WhatsAppService($settings);

        $nudged   = $this->nudgeStalledCarts($waService);
        $followed = $this->followUpUnpaidOrders($waService);

        $this->info("Nudged {$nudged} stalled cart(s), followed up on {$followed} unpaid order(s).");

        return self::SUCCESS;
    }

    private function nudgeStalledCarts(WhatsAppService $waService): int
    {
        $sessions = WhatsAppSession::whereIn('state', ['cart', 'checkout_price_confirm', 'checkout_confirm'])
            ->where('updated_at', '<=', now()->subMinutes(self::STALL_MINUTES))
            ->where('updated_at', '>=', now()->subHours(self::GIVE_UP_HOURS))
            ->get();

        $sent = 0;

        foreach ($sessions as $session) {
            if (! empty($session->data['nudge_sent'])) {
                continue;
            }

            $cart = $session->data['cart'] ?? [];
            if (empty($cart)) {
                continue;
            }

            $contact = Contact::where('phone', $session->phone)->first();
            if (! $contact || $contact->wa_opted_out) {
                continue;
            }

            $count = array_sum(array_column($cart, 'qty'));
            $itemsLabel = $count === 1 ? 'item' : 'items';

            $waService->sendTextMessage(
                $contact->phone,
                "Still there? 🥭 You have {$count} {$itemsLabel} waiting — reply *checkout* to finish your order, or *menu* to start over.\n\n— Merza Team"
            );

            // Stored on the session itself (not a DB column) so this stays a
            // one-time nudge without needing a migration for a single flag.
            $session->update(['data' => array_merge($session->data, ['nudge_sent' => true])]);

            BotActivityLog::create([
                'event_type'  => 'cart_nudge_sent',
                'contact_id'  => $contact->id,
                'raw_payload' => ['state' => $session->state, 'cart_items' => $count],
                'status'      => 'success',
            ]);

            $sent++;
        }

        return $sent;
    }

    private function followUpUnpaidOrders(WhatsAppService $waService): int
    {
        $orders = Order::where('channel', 'whatsapp')
            ->where('payment_status', 'unpaid')
            ->whereNull('payment_reference')
            ->whereNull('payment_screenshot_path')
            ->where('created_at', '<=', now()->subHours(self::UNPAID_FOLLOWUP_HOURS))
            ->where('created_at', '>=', now()->subDays(self::UNPAID_GIVE_UP_DAYS))
            ->get();

        $sent = 0;

        foreach ($orders as $order) {
            $alreadyFollowedUp = BotActivityLog::where('event_type', 'payment_followup_sent')
                ->where('raw_payload->order_id', $order->id)
                ->exists();

            if ($alreadyFollowedUp || empty($order->customer_phone)) {
                continue;
            }

            $contact = $order->contact_id ? Contact::find($order->contact_id) : null;
            if ($contact && $contact->wa_opted_out) {
                continue;
            }

            $waService->sendTextMessage(
                $order->customer_phone,
                "Hi! Just checking in — did you complete payment for order *{$order->order_number}*?\n\nReply with your UPI reference number, or send a screenshot of the payment and we'll confirm it right away. 🥭\n\n— Merza Team"
            );

            BotActivityLog::create([
                'event_type'  => 'payment_followup_sent',
                'contact_id'  => $order->contact_id,
                'raw_payload' => ['order_id' => $order->id, 'order_number' => $order->order_number],
                'status'      => 'success',
            ]);

            $sent++;
        }

        return $sent;
    }
}
