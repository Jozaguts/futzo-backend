<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

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

Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
    return $request->user();
});

Route::get('/auth/{provider}/redirect', function () {
    $url  = Socialite::driver('facebook')->stateless()->redirect()->getTargetUrl();

    return response()->json(['url' => $url]);
});

Route::get('/auth/{provider}/callback', function ($provider) {

    $user = Socialite::driver($provider)->stateless()->user();
    $authUser = User::updateOrCreate(
        [
            'email' => $user->getEmail(),
        ],
        [
            'name' => $user->getName(),
            "{$provider}_id" => $user->getId()
        ]);

        $token = $authUser->createToken('auth_token');

    return response()->json(['success' => (bool)$token, 'token'=> $token->plainTextToken]);
});


Route::middleware('auth:sanctum')->post('/logout', function (Request $request) {
    return $request->user()->tokens()->delete();
});
