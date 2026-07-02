<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('step_number');
            $table->unsignedInteger('delay_days')->default(0);
            $table->text('message');
            $table->timestamps();

            $table->unique(['campaign_id', 'step_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_steps');
    }
};
