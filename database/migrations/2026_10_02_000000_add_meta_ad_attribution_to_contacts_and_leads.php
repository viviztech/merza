<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('meta_ad_id')->nullable();
            $table->string('meta_ad_name')->nullable();
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->string('meta_lead_id')->nullable()->index();
            $table->string('meta_ad_id')->nullable();
            $table->string('meta_ad_name')->nullable();
        });

        // Older Graph API responses were kept in bot activity logs even though
        // their ad attribution was not copied to the CRM records.
        DB::table('bot_activity_logs')
            ->whereIn('event_type', ['contact_created', 'contact_updated', 'lead_created'])
            ->whereNotNull('meta_lead_id')
            ->orderBy('id')
            ->chunkById(200, function ($logs): void {
                foreach ($logs as $log) {
                    $fetched = DB::table('bot_activity_logs')
                        ->where('event_type', 'lead_fetched')
                        ->where('meta_lead_id', $log->meta_lead_id)
                        ->latest('id')
                        ->value('raw_payload');
                    $data = json_decode((string) $fetched, true);
                    $attribution = [
                        'meta_ad_id' => filled($data['ad_id'] ?? null) ? (string) $data['ad_id'] : null,
                        'meta_ad_name' => filled($data['ad_name'] ?? null) ? (string) $data['ad_name'] : null,
                    ];

                    if ($log->contact_id && array_filter($attribution)) {
                        DB::table('contacts')->where('id', $log->contact_id)->update($attribution);
                    }

                    if ($log->event_type === 'lead_created' && $log->lead_id) {
                        DB::table('leads')->where('id', $log->lead_id)->update([
                            'meta_lead_id' => $log->meta_lead_id,
                            ...$attribution,
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['meta_lead_id', 'meta_ad_id', 'meta_ad_name']);
        });
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['meta_ad_id', 'meta_ad_name']);
        });
    }
};
