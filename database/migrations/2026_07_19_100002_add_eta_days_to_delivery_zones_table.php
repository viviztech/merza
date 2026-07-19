<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_zones', function (Blueprint $table) {
            $table->unsignedTinyInteger('eta_days')->default(2)->after('rate_per_kg');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_zones', function (Blueprint $table) {
            $table->dropColumn('eta_days');
        });
    }
};
