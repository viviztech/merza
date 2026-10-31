<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('attribution_origin', 100)->nullable()->index()->after('meta_event_context');
            $table->string('attribution_source_type', 50)->nullable()->index()->after('attribution_origin');
            $table->string('attribution_source', 100)->nullable()->after('attribution_source_type');
            $table->string('attribution_medium', 100)->nullable()->after('attribution_source');
            $table->string('attribution_campaign', 255)->nullable()->after('attribution_medium');
            $table->string('attribution_term', 255)->nullable()->after('attribution_campaign');
            $table->string('attribution_content', 255)->nullable()->after('attribution_term');
            $table->string('attribution_click_id', 255)->nullable()->after('attribution_content');
            $table->string('attribution_device_type', 50)->nullable()->after('attribution_click_id');
            $table->text('attribution_referrer_url')->nullable()->after('attribution_device_type');
            $table->text('attribution_landing_url')->nullable()->after('attribution_referrer_url');
            $table->text('attribution_user_agent')->nullable()->after('attribution_landing_url');
            $table->json('attribution_data')->nullable()->after('attribution_user_agent');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['attribution_origin']);
            $table->dropIndex(['attribution_source_type']);
            $table->dropColumn([
                'attribution_origin',
                'attribution_source_type',
                'attribution_source',
                'attribution_medium',
                'attribution_campaign',
                'attribution_term',
                'attribution_content',
                'attribution_click_id',
                'attribution_device_type',
                'attribution_referrer_url',
                'attribution_landing_url',
                'attribution_user_agent',
                'attribution_data',
            ]);
        });
    }
};

