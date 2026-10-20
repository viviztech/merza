<?php

use App\Http\Controllers\Api\V1\ApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('api.key')->prefix('v1')->group(function () {
    Route::get('/', [ApiController::class, 'health'])->name('api.v1.index');
    Route::get('/health', [ApiController::class, 'health'])->name('api.v1.health');
    Route::get('/products', [ApiController::class, 'products'])->name('api.v1.products');
    Route::get('/categories', [ApiController::class, 'categories'])->name('api.v1.categories');

    Route::get('/orders', [ApiController::class, 'orders'])->name('api.v1.orders.index');
    Route::get('/orders/{order}', [ApiController::class, 'showOrder'])->name('api.v1.orders.show');
    Route::post('/orders', [ApiController::class, 'createOrder'])->name('api.v1.orders.store');
    Route::match(['put', 'patch'], '/orders/{order}', [ApiController::class, 'updateOrder'])->name('api.v1.orders.update');
});
