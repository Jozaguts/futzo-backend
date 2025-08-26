<?php

use App\Http\Controllers\Auth\PreRegisterController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\GameActionDetailController;
use App\Http\Controllers\GameGeneralDetailsController;
use App\Http\Controllers\GameTimeDetailsController;
use App\Http\Controllers\GenderController;
use App\Http\Controllers\LineupsController;
use App\Http\Controllers\OnBoardingCallbackController;
use App\Http\Controllers\PenaltyController;
use App\Http\Controllers\PenaltyGoalKeeperController;
use App\Http\Controllers\RefereeController;
use App\Http\Controllers\RoleAndPermissionsController;
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

    Route::get('/me', function (Request $request) {
        return new UserResource($request->user());
    });
    Route::prefix('admin')->group(function () {
        Route::apiResource('/roles', RoleAndPermissionsController::class);
        Route::apiResources(['genders' => GenderController::class]);
        Route::apiResources(['referees' => RefereeController::class]);
        Route::apiResources(['penalties' => PenaltyController::class]);
        Route::apiResources(['penalty-goal-keepers' => PenaltyGoalKeeperController::class]);
        Route::apiResources(['game-details' => GameGeneralDetailsController::class]);
        Route::apiResources(['game-time-details' => GameTimeDetailsController::class]);
        Route::apiResources(['game-action-details' => GameActionDetailController::class]);
        Route::apiResources(['lineups' => LineupsController::class]);
        Route::get('positions', \App\Http\Controllers\PositionsController::class);

        require __DIR__ . '/leagues/routes.php';
        require __DIR__ . '/tournaments/routes.php';
        require __DIR__ . '/teams/routes.php';
        require __DIR__ . '/dashboard/routes.php';
        require __DIR__ . '/players/routes.php';
        require __DIR__ . '/profile/routes.php';
        require __DIR__ . '/locations/routes.php';
        require __DIR__ . '/categories/routes.php';
        require __DIR__ . '/games/routes.php';
    });
});

Route::prefix('public')->group(function () {
   require __DIR__ . '/teams/public.php';
   require __DIR__ . '/tournaments/public.php';
   require __DIR__ . '/products/public.php';
});
Route::get('checkout', CheckoutController::class)
    ->middleware('checkout.eligibility')
    ->name('checkout');
Route::get('billing/callback', OnBoardingCallbackController::class)
    ->name('billing.callback');
Route::post('/pre-register', [PreRegisterController::class, 'preRegister'])
    ->middleware(['throttle:3,1'])
    ->name('pre-register');
Route::get('verify-code/resend', [UserController::class, 'resendVerifyCode'])
    ->middleware(['throttle:3,1'])
    ->name('verify-code.resend');


