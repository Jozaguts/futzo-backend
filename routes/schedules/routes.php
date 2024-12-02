<?php

use App\Http\Controllers\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::prefix('schedules')->group(function () {
	Route::post('generate', [ScheduleController::class, 'generate']);
});
