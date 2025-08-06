<?php

use App\Http\Controllers\TeamsController;
use App\Models\DefaultLineupPlayer;
use Illuminate\Support\Facades\Route;


Route::prefix('teams')->group(function () {
    Route::post('{team}/players/{player}/assign', [TeamsController::class, 'assignPlayer']);
    Route::get('{team}/next-games', [TeamsController::class, 'nextGames']);
    Route::get('{team}/last-games', [TeamsController::class, 'lastGames']);
    Route::get('{team}/formation', [TeamsController::class, 'formation']);
    Route::get('{team}/available-players', [TeamsController::class, 'getDefaultLineupAvailableTeamPlayers']);
    Route::get('list', [TeamsController::class, 'list']);
    Route::get('template', [TeamsController::class, 'downloadTeamsTemplate']);
    Route::get('search', [TeamsController::class, 'search']);
    Route::get('', [TeamsController::class, 'index'])->withoutMiddleware('auth:sanctum');
    Route::get('{id}', [TeamsController::class, 'show'])->withoutMiddleware('auth:sanctum');
    Route::post('{team}/default-lineup-players', [TeamsController::class, 'addDefaultLineupPlayer']);
    Route::post('{team}/games/{game}/lineup-players', [TeamsController::class, 'addLineupPlayer']);
    Route::put('{team}/games/{game}/formation', [TeamsController::class, 'updateGameTeamFormation']);
    Route::post('import', [TeamsController::class, 'import']);
    Route::post('', [TeamsController::class, 'store'])->withoutMiddleware('auth:sanctum');
    Route::put('{team}/default-lineup-players/{defaultLineupPlayer}', [TeamsController::class, 'updateDefaultLineupAvailableTeamPlayers']);
    Route::put('{team}/lineup-players/{lineupPlayer}', [TeamsController::class, 'updateLineupAvailableTeamPlayers']);
    Route::put('{team}/formation', [TeamsController::class, 'updateDefaultFormation']);
    Route::put('{id}', [TeamsController::class, 'update']);
    Route::delete('{id}', [TeamsController::class, 'destroy']);
});
