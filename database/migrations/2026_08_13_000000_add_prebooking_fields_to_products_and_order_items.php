<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_preorder')->default(false)->after('is_available_today');
            $table->date('available_from')->nullable()->after('is_preorder');
            $table->string('preorder_note', 180)->nullable()->after('available_from');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->boolean('is_preorder')->default(false)->after('sku');
            $table->date('available_from')->nullable()->after('is_preorder');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['is_preorder', 'available_from']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_preorder', 'available_from', 'preorder_note']);
        });
    }
};
