<?php

use App\Http\Controllers\PlayersController;
use Illuminate\Support\Facades\Route;

Route::prefix('players')->group(function () {
    Route::get('template', [PlayersController::class, 'downloadPlayersTemplate'])->name('players.export.template');
    Route::get('{player}', [PlayersController::class, 'show'])->name('players.show');
    Route::get('', [PlayersController::class, 'index'])->name('players.index');
    Route::post('import', [PlayersController::class, 'import'])->name('players.import');
    Route::post('', [PlayersController::class, 'store'])->name('players.store');
    Route::delete('{player}', [PlayersController::class, 'destroy'])->name('players.destroy');
    Route::put('{player}', [PlayersController::class, 'update'])->name('players.update');
});
