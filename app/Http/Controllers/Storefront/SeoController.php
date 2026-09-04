<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SeoController extends Controller
{
    /**
     * Dynamic XML sitemap — static pages plus every active product, with lastmod
     * driven by the underlying row so it stays accurate without manual upkeep.
     */
    public function sitemap(): Response
    {
        $xml = Cache::remember('seo:sitemap-xml', 3600, function () {
            $staticPages = [
                ['route' => 'home', 'changefreq' => 'daily', 'priority' => '1.0'],
                ['route' => 'products.index', 'changefreq' => 'daily', 'priority' => '0.9'],
                ['route' => 'about', 'changefreq' => 'monthly', 'priority' => '0.6'],
                ['route' => 'faq', 'changefreq' => 'monthly', 'priority' => '0.6'],
                ['route' => 'contact', 'changefreq' => 'monthly', 'priority' => '0.5'],
                ['route' => 'wholesale', 'changefreq' => 'monthly', 'priority' => '0.6'],
                ['route' => 'blog', 'changefreq' => 'weekly', 'priority' => '0.5'],
                ['route' => 'careers', 'changefreq' => 'monthly', 'priority' => '0.3'],
                ['route' => 'track.index', 'changefreq' => 'monthly', 'priority' => '0.3'],
                ['route' => 'privacy', 'changefreq' => 'yearly', 'priority' => '0.2'],
                ['route' => 'terms', 'changefreq' => 'yearly', 'priority' => '0.2'],
            ];

            $urls = [];

            foreach ($staticPages as $page) {
                $urls[] = [
                    'loc' => route($page['route']),
                    'lastmod' => now()->toAtomString(),
                    'changefreq' => $page['changefreq'],
                    'priority' => $page['priority'],
                ];
            }

            Product::query()
                ->where('is_active', true)
                ->select(['slug', 'updated_at', 'is_featured'])
                ->orderBy('sort_order')
                ->chunk(200, function ($products) use (&$urls) {
                    foreach ($products as $product) {
                        $urls[] = [
                            'loc' => route('products.show', $product->slug),
                            'lastmod' => $product->updated_at->toAtomString(),
                            'changefreq' => 'weekly',
                            'priority' => $product->is_featured ? '0.9' : '0.7',
                        ];
                    }
                });

            return view('sitemap', ['urls' => $urls])->render();
        });

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * llms.txt — an emerging convention (llmstxt.org) that gives AI crawlers and
     * answer engines (ChatGPT, Perplexity, Claude, Gemini, AI Overviews) a concise,
     * structured summary of the site so they can accurately cite and represent it.
     */
    public function llmsTxt(): Response
    {
        $body = Cache::remember('seo:llms-txt', 3600, function () {
            $products = Product::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->limit(30)
                ->get(['name', 'slug', 'short_description']);

            return view('llms-txt', ['products' => $products])->render();
        });

        return response($body, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
