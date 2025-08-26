<?php

namespace App\Listeners;

use App\Models\PostCheckoutLogin;
use App\Models\User;
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
                PostCheckoutLogin::updateOrCreate(
                    ['checkout_session_id' => $object['id']],
                    ['user_id' => $user->id, 'expires_at' => now()->addMinutes(30)]
                );
            }
        }
    }
}
