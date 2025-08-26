<?php

namespace App\Jobs;

use App\Models\PostCheckoutLogin;
use App\Models\ProductPrice;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;
use Spatie\WebhookClient\Models\WebhookCall;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class StripeNotificationJob extends ProcessWebhookJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $tries = 5;
    public function __construct(public WebhookCall $webhookCall)
    {
        parent::__construct($this->webhookCall);
        $this->onQueue('high');
    }
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    /**
     * @throws ApiErrorException
     */
    public function handle(): void
    {
        $payload = $this->webhookCall->payload;
        $type = $payload['type'];
        $data = $payload['data'] ?? [];
        switch ($type) {
            case 'product.created':
            case 'product.updated':
            case 'price.created':
            case 'price.updated':
                logger($type);
                break;
            case 'checkout.session.completed':
                $object = $data['object'] ?? [];
                logger('checkout.session.completed', [
                    'session_id' => $object['id'] ?? null,
                    'customer'   => $object['customer'] ?? null,
                    'subscription' => $object['subscription'] ?? null,
                    'plan_sku'   => $object['metadata']['plan_sku'] ?? null,
                ]);

                $this->handleCheckoutSessionCompleted($object);

                // limpia cache (reuso de sesiones)
                $this->closeIntentInCacheBySession(
                    $object['id'] ?? '',
                    $object['status'] ?? 'complete',
                    $object['payment_status'] ?? null
                );
                break;
            case 'checkout.session.expired':
                $object = $data['object'] ?? [];
                $this->closeIntentInCacheBySession(
                    $object['id'] ?? '',
                    'expired',
                    $object['payment_status'] ?? null
                );
                break;
            case 'invoice.payment_succeeded':
                logger('invoice.payment_succeeded');
                break;
            case 'customer.subscription.created':
                $object = $data['object'] ?? [];
//                add logic with casier
                break;
            case 'customer.subscription.updated':
                logger('customer.subscription.updated');
                break;
            case 'customer.subscription.deleted':
                logger('customer.subscription.deleted');
                break;
        }
    }

    /**
     * @throws ApiErrorException
     */
    protected function handleCheckoutSessionCompleted(array $session): void
    {
        $email = $session['customer_details']['email'] ?? ($session['metadata']['app_email'] ?? null);

        if (!$email) {
            logger('checkout.session.completed: missing email');
            return;
        }

        // Upsert user (sin password; se pedirá luego)
        $user = User::withoutEvents(static function () use ($email) {
            $user = User::firstOrCreate(['email' => $email], [
                'name' => Str::before($email, '@'),
            ]);
            try {
                $user->assignRole('administrador');
            } catch (\Throwable $e) {
                logger('role assign skipped/failed');
            }
            return $user;
        });

        // Enlaza customer (stripe_id)
        if (!empty($session['customer']) && $user->stripe_id !== $session['customer']) {
            $user->stripe_id = $session['customer'];
            $user->saveQuietly();
        }

        // Guarda mapping para auto-login
        PostCheckoutLogin::updateOrCreate(
            ['checkout_session_id' => $session['id']],
            ['user_id' => $user->id, 'expires_at' => now()->addMinutes(30)]
        );
        // Reglas para schedule (solo si aplica)
        $first = ($session['metadata']['first_purchase'] ?? '0') === '1';
        $hasSubscription = !empty($session['subscription']);

        if (!$first || !$hasSubscription) {
            return;
        }


        $stripe = new StripeClient(config('services.stripe.secret'));

        // Asegurar line_items (por si no vino expandido)
        if (empty($session['line_items'])) {
            $sess = $stripe->checkout->sessions->retrieve($session['id'], ['expand' => ['line_items']]);
            $lineItems = $sess->line_items;
        } else {
            $lineItems = (object)$session['line_items'];
        }

        $usedPriceId = $lineItems->data[0]->price->id ?? null;
        if (!$usedPriceId) {
            logger('schedule: missing usedPriceId');
            return;
        }
        $usedDbPrice = ProductPrice::where('stripe_price_id', $usedPriceId)->first();
        if (!$usedDbPrice) {
            logger('schedule: used price not found in DB', ['price_id' => $usedPriceId]);
            return;
        }
        // Condición exacta: special_first_month mensual → programar cambio a intro/month
        if (!($usedDbPrice->variant === 'special_first_month' && $usedDbPrice->billing_period === 'month')) {
            // Si fue anual o ya era intro, no hay schedule que programar
            return;
        }
        // Idempotencia (evitar schedules duplicados por reintentos)
        $subId = $session['subscription'];
        $idempoKey = "sched:sub:{$subId}";
        if (!Cache::add($idempoKey, 1, now()->addDay())) {
            // Ya se procesó recientemente
            return;
        }
        try {
            // Evita duplicar si ya existe schedule ligado a la suscripción
            $sub = $stripe->subscriptions->retrieve($subId, ['expand' => ['schedule']]);
            if (!empty($sub->schedule)) {
                logger('schedule: already exists, skip', ['subscription' => $subId, 'schedule' => $sub->schedule->id]);
                return;
            }

            // Price destino intro/month
            $planSku = (string)($session['metadata']['plan_sku'] ?? '');
            $intro   = ProductPrice::for($planSku, 'intro', 'month');
            if (!$intro?->stripe_price_id) {
                logger('schedule: intro/month not found', ['plan_sku' => $planSku]);
                return;
            }

            // 1) Crear schedule desde la suscripción (sin phases)
            $schedule = $stripe->subscriptionSchedules->create(
                [
                    'from_subscription' => $subId,
                ],
                [
                    'idempotency_key' => 'sched:create:'.$subId,
                ]
            );

            // 2) Recuperar para conocer el fin de la fase actual
            $schedule = $stripe->subscriptionSchedules->retrieve($schedule->id, ['expand' => ['current_phase']]);
            $currentPhaseStart = $schedule->current_phase->start_date ?? null;
            $currentPhaseEnd = $schedule->current_phase->end_date ?? null;


            // 3) Actualizar: fase futura → intro/month
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

            $stripe->subscriptionSchedules->update($schedule->id, $update);

            logger('schedule: programmed intro/month', [
                'subscription' => $subId,
                'schedule' => $schedule->id,
                'phase_start' => $currentPhaseEnd,
                'to_price' => $intro?->stripe_price_id,
            ]);
        } catch (\Throwable $e) {
            // No bloquear onboarding si falla: log y dejar idempotencia para reintento
            logger('schedule error: '.$e->getMessage(), ['subscription' => $subId]);
            // Permitir reintento borrando la marca si quieres:
            // Cache::forget($idempoKey);
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
