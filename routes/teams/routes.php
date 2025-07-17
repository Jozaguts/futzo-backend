<?php

use App\Http\Controllers\TeamsController;
use Illuminate\Support\Facades\Route;


Route::prefix('teams')->group(function () {
    Route::post('{team}/players/{player}/assign', [TeamsController::class, 'assignPlayer']);
    Route::get('{team}/formation', [TeamsController::class, 'formation']);
    Route::get('{team}/players', [TeamsController::class, 'players']);
    Route::get('list', [TeamsController::class, 'list']);
    Route::get('template', [TeamsController::class, 'downloadTeamsTemplate']);
    Route::get('search', [TeamsController::class, 'search']);
    Route::get('', [TeamsController::class, 'index'])->withoutMiddleware('auth:sanctum');
    Route::get('{id}', [TeamsController::class, 'show'])->withoutMiddleware('auth:sanctum');
    Route::post('import', [TeamsController::class, 'import']);
    Route::post('', [TeamsController::class, 'store'])->withoutMiddleware('auth:sanctum');
    Route::put('{id}', [TeamsController::class, 'update']);
    Route::delete('{id}', [TeamsController::class, 'destroy']);
});
