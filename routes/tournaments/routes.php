<?php

use App\Http\Controllers\TournamentController;
use Illuminate\Support\Facades\Route;


Route::prefix('tournaments')->group(function () {
    Route::get('', [TournamentController::class, 'index']);
    Route::post('', [TournamentController::class, 'store'])->withoutMiddleware('auth:sanctum');

    Route::get('{tournament}/schedule/settings', [TournamentController::class, 'scheduleSettings']);
    Route::get('{tournament}/schedule', [TournamentController::class, 'getTournamentSchedule']);
    Route::post('{tournament}/schedule', [TournamentController::class, 'schedule']);
    Route::get('{tournament}/locations', [TournamentController::class, 'getTournamentLocations']);
    Route::get('{tournament}/fields', [TournamentController::class, 'fields']);
    Route::post('{tournament}/locations', [TournamentController::class, 'storeTournamentLocations']);
    Route::put('{tournament}/status', [TournamentController::class, 'updateStatus']);
    Route::get('types', [TournamentController::class, 'getTournamentTypes']);
    Route::get('formats', [TournamentController::class, 'getTournamentFormats'])->withoutMiddleware('auth:sanctum');
    Route::get('{tournament}', [TournamentController::class, 'show'])->withoutMiddleware('auth:sanctum');
    Route::put('{tournament}', [TournamentController::class, 'update']);
    Route::post('{tournament}/rounds/{roundId}', [TournamentController::class, 'updateTournamentRound']);
    Route::put('{tournament}/schedule/rounds/{roundId}', [TournamentController::class, 'updateGameStatus']);
});
