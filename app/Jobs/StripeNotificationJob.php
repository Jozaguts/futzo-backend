<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;
use Spatie\WebhookClient\Models\WebhookCall;

class StripeNotificationJob extends ProcessWebhookJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public WebhookCall $webhookCall)
    {
        parent::__construct($this->webhookCall);
        $this->onQueue('high');
    }

    public function handle(): void
    {
        $payload = $this->webhookCall->payload;
        $type = $payload['type'];
        $data = $payload['data'];
        switch ($type) {
            case 'product.created':
                logger('Product created');
                break;
            case 'product.updated':
                logger('Product updated');
                break;
            case 'price.created':
                logger('price created');
                break;
            case 'price.updated':
                logger('price updated');
            break;
            case 'checkout.session.completed':
                logger('checkout.session.completed');
                break;
            case 'invoice.payment_succeeded':
                logger('invoice.payment_succeeded');
                break;
            case 'customer.subscription.updated':
                logger('customer.subscription.updated');
                break;
            case 'customer.subscription.deleted':
                logger('customer.subscription.deleted');
                break;
        }


    }
}
