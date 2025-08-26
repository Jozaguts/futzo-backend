<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Stripe\Exception\ApiErrorException;

class EnsureCheckoutEligibilityMiddleware
{
    /**
     * @throws ApiErrorException
     */
    public function handle(Request $request, Closure $next)
    {

        $identifier = strtolower($request->input('identifier', $request->input('email')));
        $authUser = $request->user();
        $owner = $authUser ?? User::where('email', $identifier)->first();


        // si no existe el owner pasa al checkout
        if (!is_null($owner) && $owner->subscriptions()->count() > 0) {
            return response()->json([
                'error' => 'already_has_account',
                'message' => 'Ya existe una cuenta con este correo. Inicia sesión para gestionar o cambiar tu suscripción.',
                'login_url' => config('app.frontend_url'),
                'identifier' =>$identifier
            ], 409);
        }

        return $next($request);
    }
}
