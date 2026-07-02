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
        Schema::create('bot_activity_logs', function (Blueprint $table) {
            $table->id();

            $table->enum('event_type', [
                'webhook_received',
                'lead_fetched',
                'contact_created',
                'contact_updated',
                'lead_created',
                'message_generated',
                'conversation_created',
                'error',
            ]);

            $table->string('meta_lead_id')->nullable()->index();
            $table->string('meta_form_id')->nullable();
            $table->string('meta_page_id')->nullable();

            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();

            $table->json('raw_payload')->nullable();
            $table->text('generated_message')->nullable();
            $table->text('error_message')->nullable();

            $table->string('status')->default('success'); // success | failed

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_activity_logs');
    }
};
