<?php

use App\Http\Controllers\TournamentController;
use App\Http\Controllers\BracketController;
use Illuminate\Support\Facades\Route;
use function Pest\Laravel\get;


Route::prefix('tournaments')->group(function () {
    Route::get('', [TournamentController::class, 'index']);
    Route::get('types', [TournamentController::class, 'getTournamentTypes']);
    Route::get('formats', [TournamentController::class, 'getTournamentFormats'])->withoutMiddleware('auth:sanctum');

    Route::get('{tournament}/schedule/settings', [TournamentController::class, 'scheduleSettings']);
    Route::get('{tournament}/schedule/rounds/{round}/export', [TournamentController::class, 'exportTournamentRoundScheduleAs']);
    Route::get('{tournament}/schedule/rounds/{round}', [TournamentController::class, 'getRoundDetails']);
    Route::get('{tournament}/standing/export', [TournamentController::class, 'exportStanding']);
    Route::get('{tournament}/stats/export', [TournamentController::class, 'exportStats']);
    Route::get('{tournament}/schedule', [TournamentController::class, 'getTournamentSchedule']);
    Route::get('{tournament}/locations', [TournamentController::class, 'getTournamentLocations']);
    Route::get('{tournament}/fields', [TournamentController::class, 'fields']);
    Route::get('{tournament}/standings', [TournamentController::class, 'getStandings']);
    Route::get('{tournament}/stats', [TournamentController::class, 'getStats']);
    Route::get('{tournament}/last-results', [TournamentController::class, 'getLastResults']);
    Route::get('{tournament}/next-games', [TournamentController::class, 'getNextGames']);
    Route::get('{tournament}/group-standings', [BracketController::class, 'groupStandings']);
    Route::get('{tournament}/bracket/preview', [BracketController::class, 'preview']);
    Route::get('{tournament}/bracket/suggestions', [BracketController::class, 'suggestions']);
    Route::get('{tournament}/registration/qr-code/generate', [TournamentController::class, 'qrCodeGenerate'])
        ->name('admin.tournament.registration.qr.generate');
    Route::get('{tournament}/schedule/qr-code/generate', [TournamentController::class, 'qrCodeScheduleGenerate'])
        ->name('admin.tournament.schedule.qr.generate');

    Route::get('{tournament}', [TournamentController::class, 'show'])->withoutMiddleware('auth:sanctum');

    Route::put('{tournament}/status', [TournamentController::class, 'updateStatus']);
    Route::put('{tournament}/phases/{tournamentPhase}', [TournamentController::class, 'updatePhaseStatus']);
    Route::put('{tournament}/schedule/rounds/{roundId}', [TournamentController::class, 'updateGameStatus']);
    Route::put('{tournament}', [TournamentController::class, 'update']);

    Route::post('', [TournamentController::class, 'store'])
        ->middleware('tournaments.quota')
        ->withoutMiddleware('auth:sanctum');
    Route::post('{tournament}/schedule', [TournamentController::class, 'schedule']);
    Route::post('{tournament}/phases/advance', [TournamentController::class, 'advancePhase']);
    Route::post('{tournament}/locations', [TournamentController::class, 'storeTournamentLocations']);
    Route::post('{tournament}/rounds/{roundId}', [TournamentController::class, 'updateTournamentRound']);
    Route::post('{tournament}/schedule/rounds/{roundId}/bye', [TournamentController::class, 'setTournamentRoundBye']);
    Route::post('{tournament}/schedule/rounds/{roundId}/lock', [TournamentController::class, 'setTournamentRoundSchedule']);
    Route::post('{tournament}/bracket/confirm', [BracketController::class, 'confirm']);
    Route::post('{tournament}/regenerate-calendar', [TournamentController::class, 'analyzeScheduleRegeneration']);
    Route::post('{tournament}/confirm-regeneration', [TournamentController::class, 'confirmScheduleRegeneration']);

});
