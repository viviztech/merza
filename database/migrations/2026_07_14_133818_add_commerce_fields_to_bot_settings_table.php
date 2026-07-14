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
        Schema::table('bot_settings', function (Blueprint $table) {
            $table->boolean('wa_commerce_enabled')->default(false)->after('voice_bot_enabled');
            $table->string('upi_id')->nullable()->after('wa_commerce_enabled');
            $table->string('upi_payee_name')->nullable()->after('upi_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bot_settings', function (Blueprint $table) {
            $table->dropColumn(['wa_commerce_enabled', 'upi_id', 'upi_payee_name']);
        });
    }
};
