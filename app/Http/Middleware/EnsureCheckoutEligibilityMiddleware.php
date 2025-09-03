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
        $owner = $request->user();
        // si el usuario ya tiene suscripciones, no es elegible para este checkout
        if ($owner && $owner->subscriptions()->count() > 0) {
            return response()->json([
                'error' => 'already_has_subscription',
                'message' => 'Ya tienes una suscripción activa. Gestiona tu plan desde Configuración.',
            ], 409);
        }

        return $next($request);
    }
}
