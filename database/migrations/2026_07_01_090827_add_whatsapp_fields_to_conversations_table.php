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
        Schema::table('conversations', function (Blueprint $table) {
            $table->string('wa_message_id')->nullable()->unique()->after('id');
            $table->foreignId('replied_to_id')->nullable()->after('contact_id')
                  ->constrained('conversations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['replied_to_id']);
            $table->dropColumn(['wa_message_id', 'replied_to_id']);
        });
    }
};
