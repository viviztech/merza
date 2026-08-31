<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * One-off import of ~1,836 contacts (customer list + B2B wholesale inquiries)
 * supplied as merza.xlsx. Existing contacts (matched by phone) are left
 * untouched; rows with an already-used email are imported with email=null
 * to avoid violating the unique constraint. Every inserted row is tagged
 * "xlsx_import_2026_08" so it can be identified or rolled back later.
 */
return new class extends Migration
{
    private const TAG = 'xlsx_import_2026_08';

    public function up(): void
    {
        $path = database_path('data/contacts_import_2026_08.json');

        if (! file_exists($path)) {
            return;
        }

        $rows = json_decode(file_get_contents($path), true) ?? [];

        if (empty($rows)) {
            return;
        }

        $existingPhones = collect($rows)
            ->pluck('phone')
            ->chunk(500)
            ->flatMap(fn ($chunk) => DB::table('contacts')->whereIn('phone', $chunk)->pluck('phone'))
            ->flip();

        $existingEmails = collect($rows)
            ->pluck('email')
            ->filter()
            ->chunk(500)
            ->flatMap(fn ($chunk) => DB::table('contacts')->whereIn('email', $chunk)->pluck('email'))
            ->flip();

        $now = now();

        $toInsert = collect($rows)
            ->reject(fn (array $row) => isset($existingPhones[$row['phone']]))
            ->map(function (array $row) use ($existingEmails, $now) {
                $email = $row['email'];
                if ($email !== null && isset($existingEmails[$email])) {
                    $email = null;
                }

                return [
                    'name'       => $row['name'],
                    'phone'      => $row['phone'],
                    'email'      => $email,
                    'source'     => $row['source'],
                    'tags'       => json_encode($row['tags']),
                    'notes'      => $row['notes'],
                    'city'       => $row['city'],
                    'state'      => $row['state'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            });

        $toInsert->chunk(500)->each(fn ($chunk) => DB::table('contacts')->insert($chunk->all()));
    }

    public function down(): void
    {
        DB::table('contacts')->whereJsonContains('tags', self::TAG)->delete();
    }
};
