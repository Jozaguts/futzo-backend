<?php

namespace App\Listeners;

use App\Models\League;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Laravel\Cashier\Events\WebhookReceived;

class SyncOwnerAndLeagueStatusListener implements ShouldQueue
{
    public function handle(WebhookReceived $event): void
    {
        $type = $event->payload['type'] ?? '';

        // Procesamos eventos relevantes de Stripe para estado de suscripción
        $relevant = [
            'customer.subscription.created',
            'customer.subscription.updated',
            'customer.subscription.deleted',
            'invoice.payment_succeeded',
            'invoice.payment_failed',
        ];
        if (!in_array($type, $relevant, true)) {
            return;
        }

        $object = $event->payload['data']['object'] ?? [];
        $customerId = $object['customer'] ?? ($object['customer_id'] ?? null);
        if (!$customerId) {
            return;
        }

        $user = User::where('stripe_id', $customerId)->first();
        if (!$user) {
            return;
        }

        // Derivar estado interno del usuario
        $derivedUserStatus = $this->deriveUserStatus($type, $object);
        if ($derivedUserStatus) {
            $user->status = $derivedUserStatus; // pending_onboarding|active|suspended
            $user->save();
        }

        // Propagar a ligas del owner (si existen)
        $this->syncLeaguesForOwner($user);
    }

    private function deriveUserStatus(string $type, array $object): ?string
    {
        // subscription object may have status
        $subStatus = $object['status'] ?? null; // trialing|active|past_due|unpaid|canceled|incomplete|...

        if (str_starts_with($type, 'customer.subscription')) {
            return match ($subStatus) {
                'trialing', 'active' => 'active',
                'past_due', 'unpaid', 'canceled', 'incomplete', 'incomplete_expired', 'paused' => 'suspended',
                default => null,
            };
        }

        if ($type === 'invoice.payment_succeeded') {
            return 'active';
        }
        if ($type === 'invoice.payment_failed') {
            return 'suspended';
        }

        return null;
    }

    private function syncLeaguesForOwner(User $user): void
    {
        $isOperational = $user->hasActiveSubscription();

        // Ligas donde es owner
        $leagues = League::where('owner_id', $user->id)->get();
        foreach ($leagues as $league) {
            if ($isOperational) {
                // Si el owner está al día, ligas pasan a ready salvo que estén archivadas
                if ($league->status !== League::STATUS_ARCHIVED) {
                    $league->status = League::STATUS_READY;
                    $league->save();
                }
            } else {
                // Suspender ligas si el owner no está al día
                if (!in_array($league->status, [League::STATUS_ARCHIVED], true)) {
                    $league->status = League::STATUS_SUSPENDED;
                    $league->save();
                }
            }
        }
    }
}

