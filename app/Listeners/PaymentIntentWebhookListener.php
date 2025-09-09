<?php

namespace App\Listeners;

use App\Models\Payment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Laravel\Cashier\Events\WebhookReceived;

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

        // Si es el PaymentIntent del invoice inicial de una suscripción, promover usuario/ligas
        if ($type === 'payment_intent.succeeded') {
            $customerId = $object['customer'] ?? null;
            $invoiceId = $object['invoice'] ?? null; // cuando proviene de invoice (suscripción)
            if ($customerId && $invoiceId) {
                $user = \App\Models\User::where('stripe_id', $customerId)->first();
                if ($user) {
                    // Marcar activo + sync de ligas
                    app(\App\Services\CheckoutSessionService::class)->promoteUserAndLeague($user, 'active');
                    // Opcional: conceder rol admin si aún no lo tiene
                    try { if (!$user->hasRole('administrador')) { $user->assignRole('administrador'); } } catch (\Throwable) {}

                    // Telemetría opcional: enviar Purchase a Meta si tenemos atribución
                    try {
                        $hasFb = !empty($user->fbp) || !empty($user->fbc) || !empty($user->fbclid);
                        if ($hasFb) {
                            $amount = ($object['amount_received'] ?? $object['amount'] ?? 0);
                            $currency = strtoupper($object['currency'] ?? 'MXN');
                            $postTrial = $user->trial_ends_at && now()->greaterThan($user->trial_ends_at);
                            \App\Jobs\SendMetaCapiEventJob::dispatch(
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
                        }
                    } catch (\Throwable) {}
                }
            }
        }
    }
}
