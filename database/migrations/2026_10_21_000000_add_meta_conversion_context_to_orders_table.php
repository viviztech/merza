<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Consent and the encrypted first-party matching context are saved
            // only for customers who opted in. They let a verified gateway
            // webhook later report a Purchase without reusing a browser token.
            $table->boolean('meta_tracking_consent')->nullable()->after('payment_verification_notes');
            $table->timestamp('meta_tracking_consent_at')->nullable()->after('meta_tracking_consent');
            $table->text('meta_event_context')->nullable()->after('meta_tracking_consent_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'meta_tracking_consent',
                'meta_tracking_consent_at',
                'meta_event_context',
            ]);
        });
    }
};
