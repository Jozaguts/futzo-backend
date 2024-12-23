<?php

use App\Http\Controllers\TeamsController;
use Illuminate\Support\Facades\Route;


Route::prefix('teams')->group(function () {
    Route::get('list', [TeamsController::class, 'list']);
    Route::get('', [TeamsController::class, 'index']);
    Route::get('{id}', [TeamsController::class, 'show']);
    Route::post('', [TeamsController::class, 'store']);
    Route::put('{id}', [TeamsController::class, 'update']);
    Route::delete('{id}', [TeamsController::class, 'destroy']);

});
