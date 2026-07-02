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
        Schema::create('bot_settings', function (Blueprint $table) {
            $table->id();

            // Meta / Facebook
            $table->string('meta_app_id')->nullable();
            $table->string('meta_app_secret')->nullable();
            $table->string('meta_page_id')->nullable();
            $table->text('meta_page_access_token')->nullable();
            $table->string('meta_verify_token')->default('merza_bot_verify');
            $table->string('meta_lead_form_id')->nullable();

            // Anthropic / Claude AI
            $table->text('anthropic_api_key')->nullable();
            $table->string('anthropic_model')->default('claude-sonnet-4-6');
            $table->text('follow_up_prompt_template')->nullable();

            // Behaviour
            $table->boolean('bot_enabled')->default(false);
            $table->boolean('auto_create_contact')->default(true);
            $table->boolean('auto_create_lead')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_settings');
    }
};
