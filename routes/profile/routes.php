<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('profile')->group(function () {
	Route::put('profile/{user}', [UserController::class, 'update'])->middleware('ensureUserOwnsProfile');
	Route::post('profile/{user}/image', [UserController::class, 'updateImage'])->middleware('ensureUserOwnsProfile');
	Route::put('profile/{user}/password', [UserController::class, 'updatePassword'])->middleware('ensureUserOwnsProfile');
});
