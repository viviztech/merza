<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // 'flow_reply_sent' is used by ProcessInboundWhatsAppJob but was missing from
    // the original enum, so every WhatsApp button-tap reply crashed on this insert.
    private const EVENT_TYPES = [
        'webhook_received', 'lead_fetched', 'contact_created', 'contact_updated',
        'lead_created', 'message_generated', 'conversation_created', 'error',
        'flow_reply_sent',
    ];

    private const OLD_EVENT_TYPES = [
        'webhook_received', 'lead_fetched', 'contact_created', 'contact_updated',
        'lead_created', 'message_generated', 'conversation_created', 'error',
    ];

    public function up(): void
    {
        DB::statement('ALTER TABLE bot_activity_logs DROP CONSTRAINT bot_activity_logs_event_type_check');
        DB::statement(
            "ALTER TABLE bot_activity_logs ADD CONSTRAINT bot_activity_logs_event_type_check CHECK (event_type IN ('"
            . implode("','", self::EVENT_TYPES) . "'))"
        );
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE bot_activity_logs DROP CONSTRAINT bot_activity_logs_event_type_check');
        DB::statement(
            "ALTER TABLE bot_activity_logs ADD CONSTRAINT bot_activity_logs_event_type_check CHECK (event_type IN ('"
            . implode("','", self::OLD_EVENT_TYPES) . "'))"
        );
    }
};
