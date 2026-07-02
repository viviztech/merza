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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('channel', ['whatsapp','facebook','instagram','phone','email','other'])->default('whatsapp');
            $table->enum('direction', ['inbound','outbound'])->default('inbound');
            $table->text('message');
            $table->string('media_url')->nullable();
            $table->enum('status', ['sent','delivered','read','failed'])->default('sent');
            $table->boolean('is_bot')->default(false);
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
