<?php

use App\Http\Controllers\OrderPdfController;
use App\Http\Controllers\Storefront\AccountController;
use App\Http\Controllers\Storefront\AuthController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\PagesController;
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

// Static pages
Route::get('/about',     [PagesController::class, 'about'])     ->name('about');
Route::get('/blog',      [PagesController::class, 'blog'])      ->name('blog');
Route::get('/wholesale', [PagesController::class, 'wholesale']) ->name('wholesale');
Route::get('/careers',   [PagesController::class, 'careers'])   ->name('careers');
Route::get('/privacy',   [PagesController::class, 'privacy'])   ->name('privacy');
Route::get('/terms',     [PagesController::class, 'terms'])     ->name('terms');

/*
|--------------------------------------------------------------------------
| Customer Auth Routes
|--------------------------------------------------------------------------
*/

// Named 'login' so Laravel's auth middleware redirects here for unauthenticated users
Route::get('/login',    [AuthController::class, 'showLogin'])    ->name('login');
Route::post('/login',   [AuthController::class, 'login'])        ->middleware('guest');
Route::get('/register', [AuthController::class, 'showRegister'])->name('customer.register')->middleware('guest');
Route::post('/logout',  [AuthController::class, 'logout'])      ->name('customer.logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Customer Account Routes (auth protected)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->prefix('account')->name('account.')->group(function () {
    Route::get('/',               [AccountController::class, 'dashboard'])    ->name('dashboard');
    Route::get('/orders',         [AccountController::class, 'orders'])       ->name('orders');
    Route::get('/orders/{order}', [AccountController::class, 'orderDetail'])  ->name('order.detail');
    Route::get('/profile',        [AccountController::class, 'profile'])      ->name('profile');
    Route::patch('/profile',      [AccountController::class, 'updateProfile'])->name('profile.update');
    Route::put('/password',       [AccountController::class, 'updatePassword'])->name('password.update');
});

/*
|--------------------------------------------------------------------------
| Admin PDF Download Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('admin/orders')->name('admin.orders.')->group(function () {
    Route::get('/{order}/invoice',       [OrderPdfController::class, 'invoice'])      ->name('invoice');
    Route::get('/{order}/delivery-slip', [OrderPdfController::class, 'deliverySlip']) ->name('delivery-slip');
});
Route::middleware('auth')->get('/admin/orders/daily-report', [OrderPdfController::class, 'dailyReport'])->name('admin.orders.daily-report');
Route::middleware('auth')->get('/admin/orders/delivery-challans', [OrderPdfController::class, 'deliveryChallans'])->name('admin.orders.delivery-challans');

/*
|--------------------------------------------------------------------------
| Webhook Routes (Phase 6)
|--------------------------------------------------------------------------
*/
Route::prefix('webhook')->name('webhook.')->group(function () {
    Route::get('/meta', [MetaWebhookController::class, 'verify'])->name('meta.verify');
    Route::post('/meta', [MetaWebhookController::class, 'handle'])->name('meta.handle');
});
