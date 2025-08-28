<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Laravel\Cashier\Events\WebhookReceived;

class AssignAdminRoleOnCheckoutListener implements ShouldQueue
{
    public function handle(WebhookReceived $event): void
    {
        if ($event->payload['type'] === 'checkout.session.completed') {
            $object = $event->payload['data']['object'];
            $email = $object['customer_details']['email'] ?? ($object['metadata']['app_email'] ?? null);
            if (!$email) {
                logger('checkout.session.completed: missing email');
            }
            $user = User::where('email', $email)->firstOrFail();
            try {
                $user->assignRole('administrador');
            } catch (\Throwable $e) {
                logger('role assign skipped/failed');
            }
        }
    }
}
