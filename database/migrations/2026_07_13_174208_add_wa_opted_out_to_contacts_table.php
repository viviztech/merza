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
        Schema::table('contacts', function (Blueprint $table) {
            $table->boolean('wa_opted_out')->default(false)->after('is_blocked');
            $table->timestamp('wa_opted_out_at')->nullable()->after('wa_opted_out');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['wa_opted_out', 'wa_opted_out_at']);
        });
    }
};
