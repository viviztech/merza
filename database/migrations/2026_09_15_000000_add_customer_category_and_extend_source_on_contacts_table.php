<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a "Customer Category" classification (new lead / enquiry only /
 * purchased customer / repeat customer) and extends "source" with
 * instagram, facebook, and old_excel_import — so the ~1,836 contacts
 * bulk-imported from merza.xlsx (tagged xlsx_import_2026_08) can be told
 * apart from organic website/WhatsApp contacts.
 */
return new class extends Migration
{
    private const NEW_SOURCES = [
        'meta_ads', 'whatsapp', 'referral', 'walk_in', 'website',
        'instagram', 'facebook', 'old_excel_import', 'other',
    ];

    private const OLD_SOURCES = ['meta_ads', 'whatsapp', 'referral', 'walk_in', 'website', 'other'];

    private const CATEGORIES = ['new_lead', 'enquiry_only', 'purchased_customer', 'repeat_customer'];

    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->enum('customer_category', self::CATEGORIES)->default('new_lead')->after('source');
        });

        $this->setSourceEnum(self::NEW_SOURCES, 'other');

        // Existing contacts already flagged as customers get a sensible starting category.
        DB::table('contacts')->where('is_customer', true)->update(['customer_category' => 'purchased_customer']);

        // The 2026-08 bulk Excel import is the owner's pre-website customer list —
        // reclassify those specific rows per the requested source/category.
        DB::table('contacts')
            ->whereJsonContains('tags', 'xlsx_import_2026_08')
            ->update([
                'source'            => 'old_excel_import',
                'customer_category' => 'purchased_customer',
                'is_customer'       => true,
            ]);
    }

    public function down(): void
    {
        DB::table('contacts')->where('source', 'old_excel_import')->update(['source' => 'other']);

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('customer_category');
        });

        $this->setSourceEnum(self::OLD_SOURCES, 'other');
    }

    private function setSourceEnum(array $values, string $default): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('contacts', function (Blueprint $table) use ($values, $default) {
                $table->enum('source', $values)->default($default)->change();
            });
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE contacts MODIFY source ENUM('" . implode("','", $values) . "') NOT NULL DEFAULT '{$default}'"
            );
            return;
        }

        DB::statement('ALTER TABLE contacts DROP CONSTRAINT contacts_source_check');
        DB::statement(
            "ALTER TABLE contacts ADD CONSTRAINT contacts_source_check CHECK (source IN ('"
            . implode("','", $values) . "'))"
        );
    }
};
