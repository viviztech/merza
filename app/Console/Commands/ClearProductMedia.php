<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class ClearProductMedia extends Command
{
    protected $signature   = 'media:clear-products';
    protected $description = 'Delete all media records for products (use when re-uploading to fix stale/broken records)';

    public function handle(): void
    {
        $products = Product::with('media')->get();
        $count    = 0;

        foreach ($products as $product) {
            $count += $product->media->count();
            $product->clearMediaCollection('thumbnail');
            $product->clearMediaCollection('images');
        }

        $this->info("Cleared {$count} media records from {$products->count()} products.");
    }
}
