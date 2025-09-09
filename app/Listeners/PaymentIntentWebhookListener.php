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
    }
}

