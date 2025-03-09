<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LocationController;

Route::prefix('locations')->group(function () {
    Route::get('', [LocationController::class, 'index'])->name('location.index');
    Route::post('', [LocationController::class, 'store'])->name('location.store');
    Route::get('fields', [LocationController::class, 'fields'])->name('location.fields');
    Route::get('{location}', [LocationController::class, 'show'])->name('location.show');
    Route::put('{location}', [LocationController::class, 'update'])->name('location.update');
    Route::delete('{location}', [LocationController::class, 'destroy'])->name('location.destroy');

});
