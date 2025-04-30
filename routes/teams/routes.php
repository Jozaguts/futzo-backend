<?php

use App\Http\Controllers\TeamsController;
use Illuminate\Support\Facades\Route;


Route::prefix('teams')->group(function () {
    Route::get('list', [TeamsController::class, 'list']);
    Route::get('template', [TeamsController::class, 'downloadTeamsTemplate']);
    Route::get('search', [TeamsController::class, 'search']);
    Route::get('', [TeamsController::class, 'index']);
    Route::get('{id}', [TeamsController::class, 'show']);
    Route::post('import', [TeamsController::class, 'import']);
    Route::post('', [TeamsController::class, 'store']);
    Route::put('{id}', [TeamsController::class, 'update']);
    Route::delete('{id}', [TeamsController::class, 'destroy']);
});
