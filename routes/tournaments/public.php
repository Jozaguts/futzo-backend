<?php


use App\Http\Controllers\TeamsController;
use App\Http\Controllers\TournamentController;

Route::middleware(['throttle:20,1','tournament.can_register_team'])
    ->group(function () {
        Route::get('tournaments/{tournament:slug}/registrations/catalogs', [TournamentController::class, 'catalogs']);
        // solo se utiliza en el middleware de nuxt para renderizar o no la vista
        Route::get('tournaments/{tournament:slug}/can-register', [TournamentController::class, 'canRegister']);
        Route::post('tournaments/{tournament}/pre-register-team', [TeamsController::class, 'store']);
    });
