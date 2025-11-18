<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class EnsureTournamentQuota
{
    public function handle(Request $request, Closure $next)
    {
        /** @var User|null $user */
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        $owner = $user->league?->owner ?? $user;

        if ($owner->canCreateTournament()) {
            return $next($request);
        }

        $checkoutUrl = route('checkout');
        $quota = $owner->tournamentsQuota();
        $message = $quota === 1
            ? 'Tu plan actual sólo permite un torneo. Actualiza para crear más.'
            : 'Has alcanzado el límite de torneos para tu plan. Actualiza para continuar.';

        return response()->json([
            'error' => 'tournament_quota_exceeded',
            'message' => $message,
            'plan_slug' => $owner->planSlug(),
            'plan_label' => $owner->planLabel(),
            'tournaments_quota' => $owner->tournamentsQuota(),
            'tournaments_used' => $owner->tournaments_used,
            'checkout_url' => $checkoutUrl,
        ], 402);
    }
}
