<?php

use App\Http\Controllers\Auth\AuthenticateController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

Route::prefix('auth')->group(function () {

    Route::post('/register', [AuthenticateController::class, 'register']);
    Route::get('/{provider}/redirect', function ($provider) {
        $url = Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();

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
                'verified_at' => now(),
            ]);

        Auth::login($authUser);

        return response()->noContent();
    });
    Route::post('login', [AuthenticateController::class, 'login']);
    Route::post('logout', [AuthenticateController::class, 'logout'])->name('logout');
});
