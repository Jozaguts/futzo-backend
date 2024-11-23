<?php

use App\Http\Controllers\PlayersController;
use Illuminate\Support\Facades\Route;

Route::prefix('players')->group(function () {
	Route::get('', [PlayersController::class, 'index'])->name('players.index');
	Route::get('{player}', [PlayersController::class, 'show'])->name('players.show');
	Route::post('', [PlayersController::class, 'store'])->name('players.store');
	Route::put('{player}', [PlayersController::class, 'update'])->name('players.update');
	Route::delete('{player}', [PlayersController::class, 'destroy'])->name('players.destroy');
	Route::post('import', [PlayersController::class, 'import'])->name('players.import');
});
