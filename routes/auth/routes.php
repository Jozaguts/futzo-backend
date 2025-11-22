<?php

use App\Http\Controllers\Auth\AuthenticateController;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

Route::prefix('auth')->group(function () {

    Route::post('/register', [AuthenticateController::class, 'register']);
    Route::get('/{provider}/redirect', function ($provider) {
        $url = Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();

        return response()->json(['url' => $url]);
    });
    Route::get('/{provider}/callback', function (Request $request, $provider) {
        // 1) El usuario negó el acceso en Google
        if ($request->has('error')) {
            // opcional: loguear el error y la descripción
            \Log::warning('Google OAuth denied', [
                'provider' => $provider,
                'error' => $request->input('error'),
                'error_description' => $request->input('error_description'),
            ]);

            return redirect()
                ->route('login')
                ->with('error', 'No se pudo iniciar sesión con Google (acceso denegado).');
        }

        // 2) No viene code → alguien entró directo al callback o algo salió mal
        if (!$request->has('code')) {
            \Log::warning('Google OAuth callback without code', [
                'provider' => $provider,
                'query'    => $request->query(),
            ]);

            return redirect()
                ->route('login')
                ->with('error', 'No se pudo iniciar sesión con Google. Intenta nuevamente.');
        }

        try {
            // 3) Intercambiar el code por el token
            $user = Socialite::driver($provider)->stateless()->user();
        } catch (ClientException $e) {
            // Error 400, 401, etc. de Google
            \Log::error('Google OAuth token error', [
                'provider' => $provider,
                'message'  => $e->getMessage(),
            ]);

            return redirect()
                ->route('login')
                ->with('error', 'Error al conectar con Google. Intenta nuevamente.');
        } catch (\Throwable $e) {
            // Cualquier otra cosa inesperada
            report($e);

            return redirect()
                ->route('login')
                ->with('error', 'Ocurrió un error al iniciar sesión con Google.');
        }

        $authUser = \App\Models\User::updateOrCreate(
            [
                'email' => $user->getEmail(),
            ],
            [
                'name'         => $user->getName(),
                "{$provider}_id" => $user->getId(),
                'verified_at'  => now(),
            ]
        );

        \Auth::login($authUser);

        return response()->noContent();
    });
    Route::post('login', [AuthenticateController::class, 'login'])->name('login');
    Route::post('logout', [AuthenticateController::class, 'logout'])->name('logout');
});
