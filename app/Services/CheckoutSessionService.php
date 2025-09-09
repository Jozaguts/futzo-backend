<?php

namespace App\Services;

use App\Models\PostCheckoutLogin;
use App\Models\User;

class CheckoutSessionService
{
    public function ensurePostCheckoutLogin(string $checkoutSessionId, ?User $user = null): PostCheckoutLogin
    {
        // Idempotente por checkout_session_id (índice único)
        $row = PostCheckoutLogin::firstOrNew(['checkout_session_id' => $checkoutSessionId]);

        if ($user && !$row->user_id) {
            $row->user_id = $user->id;
        }

        if (!$row->exists) {
            $row->expires_at = now()->addMinutes(30);
        } elseif (!$row->expires_at || $row->expires_at->isPast()) {
            // Extender ventana si está caducado o sin valor
            $row->expires_at = now()->addMinutes(30);
        }

        $row->save();
        return $row;
    }

    public function generateOneTimeLoginToken(PostCheckoutLogin $row): ?string
    {
        if ($row->expires_at && $row->expires_at->isFuture()) {
            $token = bin2hex(random_bytes(32));
            $row->login_token = hash('sha256', $token);
            $row->save();
            return $token;
        }
        return null;
    }

    public function promoteUserAndLeague(User $user, ?string $subscriptionStatus = null): void
    {
        if ($subscriptionStatus && in_array($subscriptionStatus, ['active', 'trialing'], true)) {
            $user->status = User::ACTIVE_STATUS;
            $user->save();
            app(LeagueStatusSyncService::class)->syncForOwner($user);
        }
    }
}

