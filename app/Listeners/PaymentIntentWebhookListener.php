<?php

namespace App\Listeners;

use App\Jobs\SendMetaCapiEventJob;
use App\Models\Payment;
use App\Models\User;
use App\Services\CheckoutSessionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Laravel\Cashier\Events\WebhookReceived;
use Laravel\Cashier\Exceptions\CustomerAlreadyCreated;

class PaymentIntentWebhookListener implements ShouldQueue
{
    public function handle(WebhookReceived $event): void
    {
        $type = $event->payload['type'] ?? '';
        $object = $event->payload['data']['object'] ?? [];
        if (!str_starts_with($type, 'payment_intent.')) {
            return;
        }

        $piId = $object['id'] ?? null;
        if (!$piId) return;

        $statusMap = [
            'payment_intent.succeeded' => 'succeeded',
            'payment_intent.payment_failed' => 'failed',
            'payment_intent.canceled' => 'canceled',
            'payment_intent.processing' => 'processing',
        ];

        $status = $statusMap[$type] ?? ($object['status'] ?? null);
        if (!$status) return;

        $attrs = ['status' => $status];
        if ($status === 'succeeded') {
            $attrs['paid_at'] = now();
        }

        Payment::where('stripe_payment_intent_id', $piId)->update($attrs);

        if ($type !== 'payment_intent.succeeded') {
            return;
        }

        $user = $this->resolveUserFromPayload($object);
        if (!$user) {
            return;
        }

        $this->ensureStripeCustomerExists($user);

        $planSlug = data_get($object, 'metadata.plan') ?? data_get($object, 'metadata.plan_sku');
        if ($planSlug) {
            $user->switchPlan($planSlug);
        }

        $invoiceId = $object['invoice'] ?? null;
        if ($invoiceId) {
            app(CheckoutSessionService::class)->promoteUserAndLeague($user, 'active');
            $this->maybeAssignAdminRole($user);
            $this->dispatchPurchaseEvent($user, $object);
        }
    }

    private function resolveUserFromPayload(array $object): ?User
    {
        $userId = data_get($object, 'metadata.user_id') ?? data_get($object, 'metadata.app_user_id');
        if ($userId) {
            return User::find($userId);
        }

        $customerId = $object['customer'] ?? null;
        if ($customerId) {
            return User::where('stripe_id', $customerId)->first();
        }

        return null;
    }

    private function ensureStripeCustomerExists(User $user): void
    {
        if ($user->stripe_id) {
            return;
        }

        try {
            $user->createAsStripeCustomer([
                'email' => (string) $user->email,
                'name' => trim(($user->name ?? '') . ' ' . ($user->last_name ?? '')),
                'phone' => $user->phone,
            ]);
        } catch (CustomerAlreadyCreated $e) {
            // Ignore: customer already created concurrently.
        }
    }

    private function maybeAssignAdminRole(User $user): void
    {
        try {
            if (!$user->hasRole('administrador')) {
                $user->assignRole('administrador');
            }
        } catch (\Throwable) {
            // noop
        }
    }

    private function dispatchPurchaseEvent(User $user, array $object): void
    {
        try {
            $hasFb = !empty($user->fbp) || !empty($user->fbc) || !empty($user->fbclid);
            if (!$hasFb) {
                return;
            }

            $amount = ($object['amount_received'] ?? $object['amount'] ?? 0);
            $currency = strtoupper($object['currency'] ?? 'MXN');
            $postTrial = $user->trial_ends_at && now()->greaterThan($user->trial_ends_at);

            SendMetaCapiEventJob::dispatch(
                eventName: 'Purchase',
                eventId: (string) \Illuminate\Support\Str::uuid(),
                userCtx: [
                    'external_id' => (string) $user->id,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'fbp' => $user->fbp,
                    'fbc' => $user->fbc,
                    'fbclid' => $user->fbclid,
                ],
                custom: [
                    'value' => $amount > 0 ? round($amount / 100, 2) : 0,
                    'currency' => $currency,
                    'is_post_trial' => (bool) $postTrial,
                ],
                eventSourceUrl: config('app.url') . '/configuracion?payment=success',
                testCode: null,
                actionSource: 'website',
                consent: true
            );
        } catch (\Throwable) {
            // swallow telemetry errors
        }
    }
}
