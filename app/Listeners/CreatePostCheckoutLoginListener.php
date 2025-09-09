<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\CheckoutSessionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Laravel\Cashier\Events\WebhookReceived;

class CreatePostCheckoutLoginListener implements ShouldQueue
{
    public function handle(WebhookReceived $event): void
    {
        if ($event->payload['type'] === 'checkout.session.completed') {
            $object = $event->payload['data']['object'];
            $email = $object['customer_details']['email'] ?? ($object['metadata']['app_email'] ?? null);
            $user = User::where('email', $email)->first();
            if ($user) {
                app(CheckoutSessionService::class)->ensurePostCheckoutLogin($object['id'], $user);
            }
        }
    }
}
