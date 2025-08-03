<?php

use App\Http\Controllers\GameController;
use Illuminate\Support\Facades\Route;


Route::prefix('games')
    ->name('games.')
    ->group(function () {
        Route::get('{game}/teams/players', [GameController::class, 'teamsPlayers'])->name('teams-players');
        Route::get('{game}/report/initialize', [GameController::class, 'initializeReport'])->name('initializeReport');
        Route::get('{game}/players', [GameController::class, 'getPlayers'])->name('players');
        Route::get('{game}/details', [GameController::class, 'show'])->name('show');
        Route::get('{game}/events', [GameController::class, 'getEvents'])->name('events');
        Route::get('formations', [GameController::class, 'formations'])->name('formations');
        Route::put('{game}/reschedule', [GameController::class, 'update'])->name('reschedule');
        Route::post('{game}/substitutions', [GameController::class, 'substitutions'])->name('substitutions');
        Route::post('{game}/cards', [GameController::class, 'cards'])->name('cards.store');
        Route::post('{game}/goals', [GameController::class, 'goals'])->name('goals.store');
        Route::delete('{game}/substitutions/{substitution}', [GameController::class, 'destroySubstitution'])->name('substitutions.destroy');
        Route::delete('{game}/game-event/{gameEvent}/card', [GameController::class, 'destroyCardGameEvent'])->name('cardGameEvent.destroy');
        Route::delete('{game}/game-event/{gameEvent}/goal', [GameController::class, 'destroyGoalGameEvent'])->name('goalGameEvent.destroy');
    });
