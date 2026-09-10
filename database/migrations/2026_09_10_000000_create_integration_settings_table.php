<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_settings', function (Blueprint $table) {
            $table->id();
            $table->string('gtm_container_id')->nullable();
            $table->string('meta_pixel_id')->nullable();
            $table->string('facebook_domain_verification')->nullable();
            $table->text('custom_head_scripts')->nullable();
            $table->text('custom_body_scripts')->nullable();
            $table->timestamps();
        });

        // Seed with the values that were previously hardcoded in the
        // storefront layout, so the live site keeps working unchanged
        // until an admin edits them.
        DB::table('integration_settings')->insert([
            'gtm_container_id'              => 'GTM-K4JQWTDC',
            'meta_pixel_id'                 => '2128624118065839',
            'facebook_domain_verification'  => 'z11x4bpkeypc1aj7dz9p2i5p08n7vl',
            'created_at'                    => now(),
            'updated_at'                    => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_settings');
    }
};
