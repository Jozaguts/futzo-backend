<?php


use App\Http\Controllers\PlayersController;
use App\Http\Controllers\TeamsController;

Route::middleware(['throttle:20,1','team.can_register_player'])
    ->group(function () {
        Route::get('teams/{team:slug}/registrations/catalogs', [TeamsController::class, 'catalogs']);
        // solo se utiliza en el middleware de nuxt para renderizar o no la vista
        Route::get('teams/{team:slug}/can-register', [TeamsController::class, 'canRegister']);
        Route::post('teams/{team}/pre-register-player', [PlayersController::class, 'store']);
    });
