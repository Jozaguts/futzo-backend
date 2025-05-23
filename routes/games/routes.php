<?php

use App\Http\Controllers\GameController;
use Illuminate\Support\Facades\Route;


Route::prefix('games')
    ->name('games.')
    ->group(function () {
        Route::get('{game}', [GameController::class, 'show'])->name('show');
        Route::put('{game}', [GameController::class, 'update'])->name('update');
    });
