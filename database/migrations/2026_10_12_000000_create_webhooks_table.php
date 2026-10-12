<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('status')->default('disabled');
            $table->string('topic');
            $table->string('delivery_url');
            $table->text('secret');
            $table->string('api_version')->default('v1');
            $table->timestamp('last_delivered_at')->nullable();
            $table->unsignedSmallInteger('last_response_status')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['topic', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhooks');
    }
};
