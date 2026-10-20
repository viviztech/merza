<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhook_deliveries', function (Blueprint $table) {
            $table->string('event_id', 64)->nullable()->after('webhook_id');
            $table->mediumText('request_body')->nullable()->after('payload_hash');
            $table->mediumText('response_body')->nullable()->after('request_body');
            $table->json('response_headers')->nullable()->after('response_body');
            $table->timestamp('last_attempted_at')->nullable()->after('error');
            $table->timestamp('locked_at')->nullable()->after('last_attempted_at');

            // New deliveries always have an event ID. Nullable keeps existing
            // audit rows intact during an in-place production migration.
            $table->unique(['webhook_id', 'event_id'], 'webhook_deliveries_webhook_event_unique');
            $table->index(['status', 'locked_at']);
        });
    }

    public function down(): void
    {
        Schema::table('webhook_deliveries', function (Blueprint $table) {
            $table->dropUnique('webhook_deliveries_webhook_event_unique');
            $table->dropIndex(['status', 'locked_at']);
            $table->dropColumn([
                'event_id',
                'request_body',
                'response_body',
                'response_headers',
                'last_attempted_at',
                'locked_at',
            ]);
        });
    }
};
