<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // New event types for the conversion-gap fixes: flow_distraction (Phase 0/1
    // instrumentation), cart_nudge_sent + payment_followup_sent (Phase 3 nudges),
    // payment_screenshot_verified (Phase 7 AI payment verification).
    private const EVENT_TYPES = [
        'webhook_received', 'lead_fetched', 'contact_created', 'contact_updated',
        'lead_created', 'message_generated', 'conversation_created', 'error',
        'flow_reply_sent', 'flow_distraction', 'cart_nudge_sent',
        'payment_followup_sent', 'payment_screenshot_verified',
    ];

    private const OLD_EVENT_TYPES = [
        'webhook_received', 'lead_fetched', 'contact_created', 'contact_updated',
        'lead_created', 'message_generated', 'conversation_created', 'error',
        'flow_reply_sent',
    ];

    public function up(): void
    {
        // SQLite DOES enforce this: Laravel's enum() compiles to a real CHECK
        // constraint there too (the comment on the migration that added
        // 'flow_reply_sent' claimed otherwise — that was wrong, just never
        // caught because no test exercised that insert path on sqlite until now).
        // Schema::change() rebuilds the table natively, no doctrine/dbal needed.
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('bot_activity_logs', function (Blueprint $table) {
                $table->enum('event_type', self::EVENT_TYPES)->change();
            });
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE bot_activity_logs MODIFY event_type ENUM('"
                . implode("','", self::EVENT_TYPES) . "') NOT NULL"
            );
            return;
        }

        DB::statement('ALTER TABLE bot_activity_logs DROP CONSTRAINT bot_activity_logs_event_type_check');
        DB::statement(
            "ALTER TABLE bot_activity_logs ADD CONSTRAINT bot_activity_logs_event_type_check CHECK (event_type IN ('"
            . implode("','", self::EVENT_TYPES) . "'))"
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('bot_activity_logs', function (Blueprint $table) {
                $table->enum('event_type', self::OLD_EVENT_TYPES)->change();
            });
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE bot_activity_logs MODIFY event_type ENUM('"
                . implode("','", self::OLD_EVENT_TYPES) . "') NOT NULL"
            );
            return;
        }

        DB::statement('ALTER TABLE bot_activity_logs DROP CONSTRAINT bot_activity_logs_event_type_check');
        DB::statement(
            "ALTER TABLE bot_activity_logs ADD CONSTRAINT bot_activity_logs_event_type_check CHECK (event_type IN ('"
            . implode("','", self::OLD_EVENT_TYPES) . "'))"
        );
    }
};
