<?php

use App\Http\Controllers\Auth\PreRegisterController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\GameActionDetailController;
use App\Http\Controllers\GameGeneralDetailsController;
use App\Http\Controllers\GameTimeDetailsController;
use App\Http\Controllers\GenderController;
use App\Http\Controllers\LineupsController;
use App\Http\Controllers\OnBoardingCallbackController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PenaltyController;
use App\Http\Controllers\PenaltyGoalKeeperController;
use App\Http\Controllers\PostCheckoutLoginController;
use App\Http\Controllers\RefereeController;
use App\Http\Controllers\RoleAndPermissionsController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\CanMakeSupportMessageRequestMiddleware;
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
Route::prefix('v1')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        Route::get('/me', function (Request $request) {
            return new UserResource($request->user()->load('league'));
        });
        Route::post('support/tickets',[SupportController::class, 'ticket'])
            ->middleware(CanMakeSupportMessageRequestMiddleware::class)
            ->name('support.tickets');
        Route::patch('support/tickets/{ticket}',[SupportController::class, 'message'])->name('support.tickets.message');
        Route::get('support/tickets', [SupportController::class, 'list'])->name('support.tickets.list');
        Route::prefix('admin')->middleware(['billing.operational'])->group(function () {
            Route::get('onboarding/steps', [OnboardingController::class, 'index']);
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
        Route::middleware('checkout.eligibility')->group(function () {
            Route::get('checkout', CheckoutController::class)
                ->name('checkout');
        });

        // Payment Intents (Stripe Elements) — no requiere billing.operational
        Route::post('payment-intents', [\App\Http\Controllers\PaymentIntentController::class, 'store']);

        // Subscription Payment Element flow (modo suscripción)
        Route::post('subscriptions/intent', [\App\Http\Controllers\SubscriptionIntentController::class, 'store']);

        // Billing Portal URL (Stripe) — gestionar suscripción/datos de pago
        Route::get('subscriptions/portal', \App\Http\Controllers\BillingPortalController::class);
});

Route::prefix('v1/public')->group(function () {
   require __DIR__ . '/teams/public.php';
   require __DIR__ . '/tournaments/public.php';
   require __DIR__ . '/products/public.php';
   Route::post('/post-checkout-login', PostCheckoutLoginController::class)->middleware('web');
});
Route::prefix('v1')
    ->group(function () {
        Route::get('billing/callback', OnBoardingCallbackController::class)
            ->name('billing.callback');
        Route::post('/pre-register', [PreRegisterController::class, 'preRegister'])
            ->middleware(['throttle:3,1'])
            ->name('pre-register');
        Route::get('verify-code/resend', [UserController::class, 'resendVerifyCode'])
            ->middleware(['throttle:3,1'])
            ->name('verify-code.resend');
    });

