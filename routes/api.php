<?php

use App\Http\Controllers\Auth\AuthenticateController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\GameActionDetailController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\GameGeneralDetailsController;
use App\Http\Controllers\GameTimeDetailsController;
use App\Http\Controllers\GenderController;
use App\Http\Controllers\LineupsController;
use App\Http\Controllers\PenaltyController;
use App\Http\Controllers\PenaltyGoalKeeperController;
use App\Http\Controllers\PlayersController;
use App\Http\Controllers\RefereeController;
use App\Http\Controllers\RoleAndPermissionsController;
use App\Http\Controllers\TeamsController;
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
include __DIR__ . '/auth/routes.php';

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/me', [AuthenticateController::class, 'me']);
    Route::post('/logout', [AuthenticateController::class, 'logout']);
    Route::prefix('/admin')->group(function () {
        Route::apiResource('/roles', RoleAndPermissionsController::class);
        Route::apiResources(['genders'=> GenderController::class]);
        Route::apiResources(['teams' => TeamsController::class]);
        Route::apiResources(['players' => PlayersController::class]);
        Route::apiResources(['categories' => CategoryController::class]);
        Route::apiResources(['referees' => RefereeController::class]);
        Route::apiResources(['penalties' => PenaltyController::class]);
        Route::apiResources(['penalty-goal-keepers' => PenaltyGoalKeeperController::class]);
        Route::apiResources(['games' => GameController::class]);
        Route::apiResources(['game-details' => GameGeneralDetailsController::class]);
        Route::apiResources(['game-time-details' => GameTimeDetailsController::class]);
        Route::apiResources(['game-action-details' => GameActionDetailController::class]);
        Route::apiResources(['lineups' => LineupsController::class]);
    });
});


