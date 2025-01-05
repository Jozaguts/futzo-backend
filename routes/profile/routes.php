<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('profile')->group(function () {
    Route::put('{user}', [UserController::class, 'update'])->middleware('ensureUserOwnsProfile');
    Route::post('{user}/image', [UserController::class, 'updateImage'])->middleware('ensureUserOwnsProfile');
    Route::put('{user}/password', [UserController::class, 'updatePassword'])->middleware('ensureUserOwnsProfile');
});
