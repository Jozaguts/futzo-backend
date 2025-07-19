<?php

use App\Http\Controllers\GameController;
use Illuminate\Support\Facades\Route;


Route::prefix('games')
    ->name('games.')
    ->group(function () {
        Route::get('formations', [GameController::class, 'formations'])->name('formations');
        Route::get('{game}/details', [GameController::class, 'show'])->name('show');
        Route::put('{game}/reschedule', [GameController::class, 'update'])->name('reschedule');
        Route::get('{game}/teams/players', [GameController::class, 'teamsPlayers'])->name('teams-players');
    });
