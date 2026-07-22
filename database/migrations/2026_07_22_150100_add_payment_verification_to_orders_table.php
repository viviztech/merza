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
        Schema::table('orders', function (Blueprint $table) {
            // Values: pending | ai_matched | ai_mismatch | ai_unclear | manually_confirmed
            // (enforced in PaymentScreenshotVerificationService, not a DB check —
            // internal field, not user input, so app-level enforcement is enough)
            $table->string('payment_verification_status')->nullable()->after('payment_screenshot_path');
            $table->decimal('payment_verified_amount', 10, 2)->nullable()->after('payment_verification_status');
            $table->text('payment_verification_notes')->nullable()->after('payment_verified_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_verification_status', 'payment_verified_amount', 'payment_verification_notes']);
        });
    }
};
