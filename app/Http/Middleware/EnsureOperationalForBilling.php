<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class EnsureOperationalForBilling
{
    public function handle(Request $request, Closure $next)
    {
        /** @var User|null $user */
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $allowed = false;

        // Allow if user has active subscription or is on trial
        if (method_exists($user, 'isOperationalForBilling') && $user->isOperationalForBilling()) {
            $allowed = true;
        }

        // Also allow if explicit user status marks as active
        if (!$allowed && ($user->status ?? null) === User::ACTIVE_STATUS) {
            $allowed = true;
        }

        if ($allowed) {
            return $next($request);
        }

        // Block: return a JSON response guiding to checkout
        $checkoutUrl = route('checkout');

        return response()->json([
            'error' => 'payment_required',
            'message' => 'Tu suscripción no está activa. Completa el pago para continuar.',
            'checkout_url' => $checkoutUrl,
            'status' => $user->status,
        ], 402);
    }
}
