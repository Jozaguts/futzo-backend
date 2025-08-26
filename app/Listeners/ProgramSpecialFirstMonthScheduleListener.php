<?php

namespace App\Listeners;

use App\Models\ProductPrice;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Laravel\Cashier\Events\WebhookReceived;
use Stripe\Exception\ApiErrorException;

class ProgramSpecialFirstMonthScheduleListener implements ShouldQueue
{
    /**
     * @throws ApiErrorException
     */
    public function handle(WebhookReceived $event): void
    {
        if ($event->payload['type'] === 'checkout.session.completed') {
            $object = $event->payload['data']['object'];
            $first = ($object['metadata']['first_purchase'] ?? '0') === '1';
            $hasSubscription = !empty($object['subscription']);
            if ($first || $hasSubscription) {
                $email = $object['customer_details']['email'] ?? ($object['metadata']['app_email'] ?? null);
                $user = User::where('email', $email)->first();
                if ($user) {
                    if (empty($object['line_items'])) {
                        $sess = $user->stripe()->checkout->sessions->retrieve($object['id'], ['expand' => ['line_items']]);
                        $lineItems = $sess->line_items;
                    }else{
                        $lineItems = (object)$object['line_items'];
                    }

                    $usedPriceId = $lineItems->data[0]->price->id ?? null;
                    if (!$usedPriceId) {
                        logger('schedule: missing usedPriceId');
                    }
                    $usedDbPrice = ProductPrice::where('stripe_price_id', $usedPriceId)->first();
                    if (!$usedDbPrice) {
                        logger('schedule: used price not found in DB', ['price_id' => $usedPriceId]);
                    }
                    if ($usedDbPrice->variant === 'special_first_month' && $usedDbPrice->billing_period === 'month') {
                        // Si no fue anual o no era intro,  hay schedule que programar
                        $subId = $object['subscription'];
                        $idempoKey = "sched:sub:{$subId}";


                        try {
                            $sub = $user->subscription('default')?->asStripeSubscription(['schedule']);

                            if (is_null($sub->schedule) && Cache::add($idempoKey, 1, now()->addDay())) {
                                $planSku = (string)($sub['metadata']['plan_sku'] ?? '');
                                $intro = ProductPrice::for($planSku, 'intro', 'month');
                                if ($intro?->stripe_price_id) {
                                    $schedule = $user->stripe()->subscriptionSchedules->create(
                                        [
                                            'from_subscription' => $subId,
                                        ],
                                        [
                                            'idempotency_key' => 'sched:create:'.$subId,
                                        ]
                                    );
                                    $schedule = $user->stripe()->subscriptionSchedules->retrieve($schedule->id, ['expand' => ['current_phase']]);
                                    $currentPhaseStart = $schedule->current_phase->start_date ?? null;
                                    $currentPhaseEnd = $schedule->current_phase->end_date ?? null;
                                    $update = [
                                        'phases' => [
                                            [
                                                'items' => [
                                                    [
                                                        'price' => $usedPriceId,
                                                        'quantity' => 1,
                                                    ]
                                                ],
                                                'start_date' => $currentPhaseStart,
                                                'end_date' => $currentPhaseEnd,
                                            ],
                                            [
                                                'items' => [
                                                    [
                                                        'price' => $intro?->stripe_price_id,
                                                        'quantity' => 1
                                                    ]
                                                ],
                                            ]
                                        ],
                                    ];
                                    if ($currentPhaseEnd) {
                                        $update['phases'][1]['start_date'] = $currentPhaseEnd;
                                    }
                                    $user->stripe()->subscriptionSchedules->update($schedule->id, $update);

                                    logger('schedule: programmed intro/month', [
                                        'subscription' => $subId,
                                        'schedule' => $schedule->id,
                                        'phase_start' => $currentPhaseEnd,
                                        'to_price' => $intro?->stripe_price_id,
                                    ]);
                                    $this->closeIntentInCacheBySession(
                                        $object['id'] ?? '',
                                        $object['status'] ?? 'complete',
                                        $object['payment_status'] ?? null
                                    );
                                }
                            }
                        }catch(\Throwable $e){
                            logger('schedule error: '.$e->getMessage(), ['subscription' => $subId]);
                        }
                    }
                }
            }
        }
    }
    protected function closeIntentInCacheBySession(string $sessionId, string $status, ?string $paymentStatus = null): void
    {
        $sidKey = "checkout:intent:by_session:{$sessionId}";
        $intentKey = Cache::get($sidKey);
        if ($intentKey) {
            // borra la intención principal
            Cache::forget($intentKey);
            Cache::forget($sidKey);
        }
    }
}
