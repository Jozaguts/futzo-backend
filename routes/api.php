<?php

use App\Http\Controllers\Auth\AuthenticateController;
use App\Http\Controllers\RoleAndPermissionsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/me', [AuthenticateController::class, 'me']);
    Route::post('/logout', [AuthenticateController::class, 'logout']);
    Route::prefix('/admin')->group(function () {
        Route::apiResource('/roles', RoleAndPermissionsController::class);
    });
});

include __DIR__ . '/auth/routes.php';
