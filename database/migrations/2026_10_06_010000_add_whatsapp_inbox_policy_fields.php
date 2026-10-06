<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category', 20);
            $table->string('source', 80);
            $table->text('evidence');
            $table->timestamp('granted_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['contact_id', 'category', 'revoked_at']);
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->string('failure_reason', 255)->nullable();
        });

        Schema::table('bot_settings', function (Blueprint $table) {
            $table->string('whatsapp_business_account_id')->nullable();
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->string('whatsapp_inbox_status', 20)->default('open');
        });

        Schema::create('whatsapp_inbox_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['contact_id', 'created_at']);
        });

        Schema::create('whatsapp_saved_replies', function (Blueprint $table) {
            $table->id();
            $table->string('title', 80);
            $table->text('body');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_saved_replies');
        Schema::dropIfExists('whatsapp_inbox_notes');

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('whatsapp_inbox_status');
        });
        Schema::table('bot_settings', function (Blueprint $table) {
            $table->dropColumn('whatsapp_business_account_id');
        });
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('failure_reason');
        });

        Schema::dropIfExists('whatsapp_consents');
    }
};
