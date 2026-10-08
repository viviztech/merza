<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $products = DB::table('products')->select('id', 'slug')->get();
            $normalized = [];

            foreach ($products as $product) {
                $slug = Str::lower($product->slug);
                if (isset($normalized[$slug])) {
                    throw new RuntimeException("Products {$normalized[$slug]} and {$product->id} have conflicting slugs after lowercasing.");
                }
                $normalized[$slug] = $product->id;
            }

            foreach ($products as $product) {
                $slug = Str::lower($product->slug);
                if ($slug !== $product->slug) {
                    DB::table('products')->where('id', $product->id)->update(['slug' => $slug]);
                }
            }
        });
    }

    public function down(): void
    {
        // Original casing cannot be reconstructed safely.
    }
};
