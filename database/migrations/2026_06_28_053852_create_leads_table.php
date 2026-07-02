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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('stage', ['new','contacted','interested','quoted','converted','lost'])->default('new');
            $table->enum('source', ['meta_ads','whatsapp','referral','walk_in','website','other'])->default('other');
            $table->string('product_interest')->nullable();
            $table->decimal('estimated_value', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
