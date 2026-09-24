<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->decimal('shipping_weight_kg', 8, 3)->nullable()->after('weight_unit');
        });

        Schema::table('delivery_settings', function (Blueprint $table) {
            $table->decimal('minimum_chargeable_weight_kg', 5, 2)->default(1)->after('free_weight_kg');
            $table->decimal('billing_weight_step_kg', 5, 2)->default(0.5)->after('minimum_chargeable_weight_kg');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('shipping_weight_kg');
        });

        Schema::table('delivery_settings', function (Blueprint $table) {
            $table->dropColumn(['minimum_chargeable_weight_kg', 'billing_weight_step_kg']);
        });
    }
};
