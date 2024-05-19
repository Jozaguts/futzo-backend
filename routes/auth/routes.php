<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticateController;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

Route::prefix('auth')->group(function () {

    Route::post('/register', [AuthenticateController::class, 'register']);
    Route::get('/{provider}/redirect', function ($provider) {
        $url  = Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();

        return response()->json(['url' => $url]);
    });
    Route::get('/{provider}/callback', function ($provider) {

        $user = Socialite::driver($provider)->stateless()->user();
        $authUser = User::updateOrCreate(
            [
                'email' => $user->getEmail(),
            ],
            [
                'name' => $user->getName(),
                "{$provider}_id" => $user->getId(),
                'lastname' =>  $user->getName(),
                'email_verified_at' => now(),
            ]);

            Auth::login($authUser);

        return response()->noContent();
    });
    Route::post('login', [AuthenticateController::class, 'login']);
    Route::post('logout', [AuthenticateController::class, 'logout']);
});
