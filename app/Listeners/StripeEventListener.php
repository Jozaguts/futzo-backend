<?php

namespace App\Listeners;

use Laravel\Cashier\Events\WebhookReceived;

class StripeEventListener
{
    public function handle(WebhookReceived $event): void
    {
        $object = $event->payload['data']['object'];
        $email = $object['customer_details']['email'] ?? ($object['metadata']['app_email'] ?? null);
//        $object = $data['object'] ?? [];

        if ($event->payload['type'] === 'checkout.session.completed') {
            logger('data', [
                'email' => $email,
                'object' => $object
            ]);
        }
    }
}
