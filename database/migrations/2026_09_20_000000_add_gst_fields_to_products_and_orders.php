<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('gst_rate', 5, 2)->default(0)->after('base_price');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('gst_rate', 5, 2)->default(0)->after('subtotal');
            $table->decimal('gst_amount', 10, 2)->default(0)->after('gst_rate');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('gst_total', 10, 2)->default(0)->after('subtotal');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('gst_total');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['gst_rate', 'gst_amount']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('gst_rate');
        });
    }
};
