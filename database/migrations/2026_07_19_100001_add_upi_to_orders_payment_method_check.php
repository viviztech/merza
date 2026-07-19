<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Storefront checkout now lets the customer pick UPI or COD explicitly,
    // instead of always recording 'whatsapp' regardless of what happened.
    private const NEW_METHODS = ['cod', 'upi', 'bank_transfer', 'whatsapp'];
    private const OLD_METHODS = ['cod', 'bank_transfer', 'whatsapp'];

    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE orders MODIFY payment_method ENUM('"
                . implode("','", self::NEW_METHODS) . "') NOT NULL DEFAULT 'cod'"
            );
            return;
        }

        DB::statement('ALTER TABLE orders DROP CONSTRAINT orders_payment_method_check');
        DB::statement(
            "ALTER TABLE orders ADD CONSTRAINT orders_payment_method_check CHECK (payment_method IN ('"
            . implode("','", self::NEW_METHODS) . "'))"
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE orders MODIFY payment_method ENUM('"
                . implode("','", self::OLD_METHODS) . "') NOT NULL DEFAULT 'cod'"
            );
            return;
        }

        DB::statement('ALTER TABLE orders DROP CONSTRAINT orders_payment_method_check');
        DB::statement(
            "ALTER TABLE orders ADD CONSTRAINT orders_payment_method_check CHECK (payment_method IN ('"
            . implode("','", self::OLD_METHODS) . "'))"
        );
    }
};
