<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['broadcast', 'drip', 'follow_up'])->default('broadcast');
            $table->enum('status', ['draft', 'scheduled', 'active', 'paused', 'completed', 'cancelled'])->default('draft');
            $table->enum('channel', ['whatsapp', 'facebook', 'email', 'sms'])->default('whatsapp');

            // Contact filters
            $table->json('filter_tags')->nullable();
            $table->string('filter_source')->nullable();
            $table->string('filter_city')->nullable();

            // Message (broadcast + follow_up; drip uses campaign_steps)
            $table->text('message')->nullable();

            // Scheduling
            $table->timestamp('scheduled_at')->nullable();
            $table->unsignedInteger('follow_up_after_days')->nullable();

            // Stats
            $table->unsignedInteger('total_contacts')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
