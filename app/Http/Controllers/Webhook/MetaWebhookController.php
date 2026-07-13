<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessInboundWhatsAppJob;
use App\Jobs\ProcessMetaLeadJob;
use App\Models\BotSetting;
use App\Services\MetaLeadsService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class MetaWebhookController extends Controller
{
    /**
     * GET /webhook/meta
     * Facebook calls this to verify the webhook subscription.
     */
    public function verify(Request $request): Response
    {
        $settings = BotSetting::current();
        $service  = new MetaLeadsService($settings);

        $challenge = $service->verifyWebhook($request->query());

        if ($challenge === false) {
            Log::warning('Meta webhook verification failed', $request->query());
            return response('Forbidden', 403);
        }

        return response($challenge, 200);
    }

    /**
     * POST /webhook/meta
     * Receives both Meta Lead Ads (page) and WhatsApp messages (whatsapp_business_account).
     */
    public function handle(Request $request): Response
    {
        $payload = $request->all();
        $object  = $payload['object'] ?? '';

        Log::info('Meta webhook received', ['object' => $object]);

        $settings = BotSetting::current();

        // Route to appropriate handler
        if ($object === 'whatsapp_business_account') {
            $this->handleWhatsApp($payload, $settings);
        } elseif ($object === 'page') {
            $this->handleLeadGen($payload, $settings);
        }

        // Always respond 200 immediately — Facebook will retry on non-200
        return response('EVENT_RECEIVED', 200);
    }

    private function handleLeadGen(array $payload, BotSetting $settings): void
    {
        if (! $settings->bot_enabled) {
            return;
        }

        $service = new MetaLeadsService($settings);
        $entries = $service->extractLeadEntries($payload);

        foreach ($entries as $entry) {
            if (empty($entry['lead_id'])) {
                continue;
            }

            ProcessMetaLeadJob::dispatch(
                $entry['lead_id'],
                $entry['form_id'] ?? '',
                $entry['page_id'] ?? '',
                $payload,
            );
        }
    }

    private function handleWhatsApp(array $payload, BotSetting $settings): void
    {
        $waService = new WhatsAppService($settings);
        $messages  = $waService->parseInboundMessages($payload);

        foreach ($messages as $msg) {
            if (empty($msg['from']) || empty($msg['wa_message_id'])) {
                continue;
            }

            ProcessInboundWhatsAppJob::dispatch(
                $msg['from'],
                $msg['wa_message_id'],
                $msg['body'],
                (int) $msg['timestamp'],
                $msg['type']            ?? 'text',
                $msg['media_id']        ?? null,
                $msg['referral']        ?? null,
                $msg['interactive_id']  ?? null,
            );
        }
    }
}
