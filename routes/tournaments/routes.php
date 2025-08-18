<?php

use App\Http\Controllers\TournamentController;
use Illuminate\Support\Facades\Route;
use function Pest\Laravel\get;


Route::prefix('tournaments')->group(function () {
    Route::get('', [TournamentController::class, 'index']);
    Route::get('types', [TournamentController::class, 'getTournamentTypes']);
    Route::get('formats', [TournamentController::class, 'getTournamentFormats'])->withoutMiddleware('auth:sanctum');

    Route::get('{tournament}/schedule/settings', [TournamentController::class, 'scheduleSettings']);
    Route::get('{tournament}/schedule/rounds/{round}/export', [TournamentController::class, 'exportTournamentRoundScheduleAs']);
    Route::get('{tournament}/standing/export', [TournamentController::class, 'exportStanding']);
    Route::get('{tournament}/stats/export', [TournamentController::class, 'exportStats']);
    Route::get('{tournament}/schedule', [TournamentController::class, 'getTournamentSchedule']);
    Route::get('{tournament}/locations', [TournamentController::class, 'getTournamentLocations']);
    Route::get('{tournament}/fields', [TournamentController::class, 'fields']);
    Route::get('{tournament}/standings', [TournamentController::class, 'getStandings']);
    Route::get('{tournament}/stats', [TournamentController::class, 'getStats']);
    Route::get('{tournament}/last-results', [TournamentController::class, 'getLastResults']);
    Route::get('{tournament}/next-games', [TournamentController::class, 'getNextGames']);

    Route::get('{tournament}', [TournamentController::class, 'show'])->withoutMiddleware('auth:sanctum');

    Route::put('{tournament}/status', [TournamentController::class, 'updateStatus']);
    Route::put('{tournament}/schedule/rounds/{roundId}', [TournamentController::class, 'updateGameStatus']);
    Route::put('{tournament}', [TournamentController::class, 'update']);

    Route::post('', [TournamentController::class, 'store'])->withoutMiddleware('auth:sanctum');
    Route::post('{tournament}/schedule', [TournamentController::class, 'schedule']);
    Route::post('{tournament}/locations', [TournamentController::class, 'storeTournamentLocations']);
    Route::post('{tournament}/rounds/{roundId}', [TournamentController::class, 'updateTournamentRound']);

});
