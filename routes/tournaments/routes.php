<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TournamentController;


Route::get('tournaments/types', [TournamentController::class, 'getTournamentTypes']);
Route::get('tournaments/formats', [TournamentController::class, 'getTournamentFormats']);
Route::put('tournaments/{tournament}/status', [TournamentController::class, 'updateStatus']);
Route::apiResources(['tournaments' => TournamentController::class]);
