<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // payment_reference is retained for the generic admin UI. These
            // columns retain the Razorpay IDs needed for server-side checks.
            $table->string('razorpay_order_id')->nullable()->unique()->after('payment_reference');
            $table->string('razorpay_payment_id')->nullable()->unique()->after('razorpay_order_id');
            $table->string('razorpay_payment_status')->nullable()->after('razorpay_payment_id');
            $table->timestamp('payment_verified_at')->nullable()->after('razorpay_payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['razorpay_order_id']);
            $table->dropUnique(['razorpay_payment_id']);
            $table->dropColumn([
                'razorpay_order_id',
                'razorpay_payment_id',
                'razorpay_payment_status',
                'payment_verified_at',
            ]);
        });
    }
};
