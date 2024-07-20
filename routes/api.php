<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\GameActionDetailController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\GameGeneralDetailsController;
use App\Http\Controllers\GameTimeDetailsController;
use App\Http\Controllers\GenderController;
use App\Http\Controllers\LeaguesController;
use App\Http\Controllers\LineupsController;
use App\Http\Controllers\PenaltyController;
use App\Http\Controllers\PenaltyGoalKeeperController;
use App\Http\Controllers\PlayersController;
use App\Http\Controllers\RefereeController;
use App\Http\Controllers\RoleAndPermissionsController;
use App\Http\Controllers\TeamsController;
use App\Http\Controllers\UserController;
use App\Http\Resources\UserResource;
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

    Route::get('/me', function(Request $request){
        return new UserResource($request->user());
    });

    Route::prefix('/admin')->group(function () {
        Route::put('/profile/{user}', [UserController::class, 'update'])->middleware('ensureUserOwnsProfile');
        Route::post('/profile/{user}/avatar', [UserController::class, 'updateAvatar'])->middleware('ensureUserOwnsProfile');
        Route::get('leagues/football/types', [LeaguesController::class, 'getFootballTypes']);
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
        Route::apiResources(['leagues' => \App\Http\Controllers\LeaguesController::class]);
        Route::apiResources(['locations' => \App\Http\Controllers\LocationController::class]);
        Route::get('leagues/{leagueId}/tournaments', [\App\Http\Controllers\LeaguesController::class, 'getTournaments']);
        Route::post('schedule/generate', [\App\Http\Controllers\ScheduleController::class, 'generate']);

        require __DIR__.'/tournaments/routes.php';
    });
});


