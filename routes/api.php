<?php

use App\Http\Controllers\Api\V1\ApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('api.key')->prefix('v1')->group(function () {
    Route::get('/', [ApiController::class, 'health'])->name('api.v1.index');
    Route::get('/health', [ApiController::class, 'health'])->name('api.v1.health');
    Route::get('/products', [ApiController::class, 'products'])->name('api.v1.products');
    Route::get('/categories', [ApiController::class, 'categories'])->name('api.v1.categories');
});
