<?php

use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Webhook\MetaWebhookController;
use App\Livewire\Storefront\CartPanel;
use App\Livewire\Storefront\CheckoutForm;
use App\Livewire\Storefront\ProductCatalogue;
use App\Livewire\Storefront\ProductDetail;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/products', ProductCatalogue::class)->name('products.index');
Route::get('/products/{slug}', ProductDetail::class)->name('products.show');

Route::get('/cart', CartPanel::class)->name('cart.index');
Route::get('/cart/count', fn () => response()->json(['count' => session('cart_count', 0)]))->name('cart.count');
Route::get('/checkout', CheckoutForm::class)->name('checkout.index');

// Customer account (Phase 4)
Route::middleware('auth')->prefix('account')->name('account.')->group(function () {
    Route::get('/orders', fn () => view('storefront.home'))->name('orders');
});

// Redirect /login to Filament admin panel
Route::redirect('/login', '/admin/login')->name('login');

/*
|--------------------------------------------------------------------------
| Webhook Routes (Phase 6)
|--------------------------------------------------------------------------
*/
Route::prefix('webhook')->name('webhook.')->group(function () {
    Route::get('/meta', [MetaWebhookController::class, 'verify'])->name('meta.verify');
    Route::post('/meta', [MetaWebhookController::class, 'handle'])->name('meta.handle');
});
