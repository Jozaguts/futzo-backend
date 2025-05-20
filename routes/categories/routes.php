<?php

use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('categories')
    ->name('categories.')
    ->group(function () {
        Route::get('', [CategoryController::class, 'index'])->name('index')->withoutMiddleware('auth:sanctum');
        Route::post('', [CategoryController::class, 'store'])->name('store')->middleware('hasNotCategory');
        Route::put('{category}', [CategoryController::class, 'update'])->name('update');

    });
