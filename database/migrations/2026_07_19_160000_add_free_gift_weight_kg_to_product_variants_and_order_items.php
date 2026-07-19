<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->decimal('free_gift_weight_kg', 8, 3)->nullable()->after('free_gift_label');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('free_gift_weight_kg', 8, 3)->nullable()->after('free_gift_label');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('free_gift_weight_kg');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('free_gift_weight_kg');
        });
    }
};
