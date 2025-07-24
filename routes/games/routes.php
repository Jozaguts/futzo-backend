<?php

use App\Http\Controllers\GameController;
use Illuminate\Support\Facades\Route;


Route::prefix('games')
    ->name('games.')
    ->group(function () {
        Route::get('{game}/teams/players', [GameController::class, 'teamsPlayers'])->name('teams-players');
        Route::get('{game}/report/initialize', [GameController::class, 'initializeReport'])->name('initializeReport');
        Route::get('{game}/players', [GameController::class, 'getPlayers'])->name('game.players');
        Route::get('{game}/details', [GameController::class, 'show'])->name('show');

        Route::get('formations', [GameController::class, 'formations'])->name('formations');
        Route::put('{game}/reschedule', [GameController::class, 'update'])->name('reschedule');
    });
