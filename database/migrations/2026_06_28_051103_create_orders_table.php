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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Customer info (captured at checkout for guest + logged-in)
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email')->nullable();
            $table->text('delivery_address');
            $table->string('city')->nullable();
            $table->string('postcode', 10)->nullable();
            $table->string('state')->nullable();

            // Financials
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);

            // Order lifecycle
            $table->enum('status', ['pending','confirmed','preparing','delivering','delivered','cancelled'])
                  ->default('pending');
            $table->enum('payment_method', ['cod','bank_transfer','whatsapp'])->default('cod');
            $table->enum('payment_status', ['unpaid','paid','refunded'])->default('unpaid');

            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
