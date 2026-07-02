<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bot_settings', function (Blueprint $table) {
            $table->string('whatsapp_phone_number_id')->nullable()->after('meta_lead_form_id');
            $table->text('whatsapp_access_token')->nullable()->after('whatsapp_phone_number_id');
            $table->boolean('wa_bot_enabled')->default(false)->after('auto_create_lead');
            $table->boolean('wa_auto_send')->default(false)->after('wa_bot_enabled');
            $table->text('wa_reply_prompt_template')->nullable()->after('follow_up_prompt_template');
        });
    }

    public function down(): void
    {
        Schema::table('bot_settings', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_phone_number_id', 'whatsapp_access_token',
                'wa_bot_enabled', 'wa_auto_send', 'wa_reply_prompt_template',
            ]);
        });
    }
};
