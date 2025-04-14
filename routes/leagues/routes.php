<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LeaguesController;

Route::prefix('leagues')
    ->name('leagues.')
    ->group(function () {
        Route::get('', [LeaguesController::class, 'index'])->name('index');
        Route::post('', [LeaguesController::class, 'store'])->name('store')->middleware('hasNotLeague');
        Route::get('locations', [LeaguesController::class, 'leagueLocations']);
        Route::get('{league}/tournaments', [LeaguesController::class, 'getTournaments'])->name('store');
        Route::get('football/types', [LeaguesController::class, 'getFootballTypes'])->name('store');
    });
