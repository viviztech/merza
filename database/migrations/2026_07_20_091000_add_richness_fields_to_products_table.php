<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->date('harvest_date')->nullable()->after('description');
            $table->string('farm_location')->nullable()->after('harvest_date');
            $table->string('sweetness_level')->nullable()->after('farm_location');
            $table->string('delivery_time')->nullable()->after('sweetness_level');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['harvest_date', 'farm_location', 'sweetness_level', 'delivery_time']);
        });
    }
};
