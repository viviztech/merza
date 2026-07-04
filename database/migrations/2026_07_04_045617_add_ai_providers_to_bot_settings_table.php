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
            $table->string('ai_provider')->default('groq')->after('anthropic_model');
            $table->string('groq_api_key')->nullable()->after('ai_provider');
            $table->string('groq_model')->default('llama-3.1-8b-instant')->after('groq_api_key');
            $table->string('sarvam_api_key')->nullable()->after('groq_model');
            $table->boolean('voice_bot_enabled')->default(false)->after('wa_auto_send');
        });
    }

    public function down(): void
    {
        Schema::table('bot_settings', function (Blueprint $table) {
            $table->dropColumn(['ai_provider', 'groq_api_key', 'groq_model', 'sarvam_api_key', 'voice_bot_enabled']);
        });
    }
};
