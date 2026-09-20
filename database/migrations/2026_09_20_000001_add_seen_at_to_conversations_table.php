<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->timestamp('seen_at')->nullable()->after('sent_at')->index();
        });

        // Existing messages pre-date the inbox, so only newly arriving messages
        // should show up as unread after this migration is deployed.
        DB::table('conversations')
            ->where('direction', 'inbound')
            ->whereNull('seen_at')
            ->update(['seen_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex(['seen_at']);
            $table->dropColumn('seen_at');
        });
    }
};
