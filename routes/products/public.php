<?php


use App\Http\Controllers\ProductController;

Route::prefix('products')->group(function () {
        Route::get('prices',[ProductController::class, 'prices']);
    });
