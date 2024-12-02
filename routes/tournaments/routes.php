<?php

use App\Http\Controllers\TournamentController;
use Illuminate\Support\Facades\Route;


Route::prefix('tournaments')->group(function () {
	Route::get('', [TournamentController::class, 'index']);
	Route::post('', [TournamentController::class, 'store']);
	Route::post('{tournament}', [TournamentController::class, 'show']);
	Route::post('{tournament}', [TournamentController::class, 'update']);
	Route::get('types', [TournamentController::class, 'getTournamentTypes']);
	Route::get('formats', [TournamentController::class, 'getTournamentFormats']);
	Route::put('{tournament}/status', [TournamentController::class, 'updateStatus']);
	Route::get('{tournament}/schedule/settings', [TournamentController::class, 'scheduleSettings']);
	Route::get('{tournament}/locations', [TournamentController::class, 'getTournamentLocations']);
	Route::post('{tournament}/locations', [TournamentController::class, 'storeTournamentLocations']);


});
