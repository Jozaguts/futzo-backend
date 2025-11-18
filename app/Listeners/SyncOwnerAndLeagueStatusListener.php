<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\LeagueStatusSyncService;
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

        $this->syncUserPlan($user, $type, $object);

        // Propagar a ligas del owner (si existen). Si aún no hay liga, no hace nada.
        app(LeagueStatusSyncService::class)->syncForOwner($user);
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

    private function syncUserPlan(User $user, string $type, array $object): void
    {
        $planSlug = data_get($object, 'metadata.plan') ?? data_get($object, 'metadata.plan_sku');
        $status = $object['status'] ?? null;

        $activeStatuses = ['trialing', 'active'];
        $inactiveStatuses = ['past_due', 'unpaid', 'canceled', 'incomplete', 'incomplete_expired', 'paused'];

        if ($planSlug && in_array($type, ['customer.subscription.created', 'customer.subscription.updated'], true)) {
            if (in_array($status, $activeStatuses, true)) {
                $user->switchPlan($planSlug);
            } elseif (in_array($status, $inactiveStatuses, true)) {
                $user->switchPlan(User::PLAN_FREE);
            }
        }

        if (in_array($type, ['customer.subscription.deleted', 'invoice.payment_failed'], true)) {
            $user->switchPlan(User::PLAN_FREE);
        }
    }
}
