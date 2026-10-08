<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('storage_instructions')->nullable();
            $table->string('shelf_life', 150)->nullable();
            $table->text('nutrition_information')->nullable();
            $table->text('packaging_details')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['storage_instructions', 'shelf_life', 'nutrition_information', 'packaging_details']);
        });
    }
};
