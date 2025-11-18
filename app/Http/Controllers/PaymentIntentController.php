<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\ProductPrice;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PaymentIntentController extends Controller
{
    /**
     * Crea (o reutiliza) un Payment Intent para el usuario autenticado.
     * Request JSON:
     *  - plan: sku del plan (p.ej. "kickoff")
     *  - period: 'month' | 'year'
     * El monto/moneda se derivan de ProductPrice (variant = 'intro').
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plan' => 'required|string',
            'period' => 'required|string|in:month,year',
        ]);

        $user = $request->user();
        $planSlug = (string) $data['plan'];
        $planDefinition = $this->ensurePlanPurchaseAllowed($user, $planSlug);

        $productPrice = ProductPrice::for($planSlug, 'intro', $data['period']);
        abort_unless(is_null($productPrice), 422, 'Plan o periodo inválido');

        $amount = (int) $productPrice->price; // centavos
        $currency = strtoupper($productPrice->currency?->iso_code ?: 'MXN');
        $purpose = 'subscription_checkout_prep';
        $description = sprintf('Pago %s (%s)', $planSlug, $data['period']);

        // Asegurar y sincronizar stripe customer (para prefill de email) solo si ya existe
        if ($user->hasStripeId()) {
            try {
                $user->updateStripeCustomer([
                    'email' => (string) $user->email,
                    'name'  => trim($user->name . ' ' . ($user->last_name ?? '')),
                    'phone' => $user->phone,
                ]);
            } catch (\Throwable $e) {}
        }

        // Idempotencia por usuario+plan+periodo (variant intro)
        $intentKey = sprintf('pi:create:%d:plan:%s:%s:intro', $user->id, $planSlug, $data['period']);
        $cached = Cache::get($intentKey);
        $stripe = $user->stripe();

        if ($cached && isset($cached['id'])) {
            try {
                $existing = $stripe->paymentIntents->retrieve($cached['id']);
                if (in_array($existing->status, ['requires_payment_method','requires_confirmation','requires_action'], true)) {
                    // Reflejar en base de datos y regresar client_secret
                    Payment::updateOrCreate(
                        ['stripe_payment_intent_id' => $existing->id],
                        [
                            'user_id' => $user->id,
                            'amount' => $amount,
                            'currency' => $currency,
                            'status' => $existing->status,
                        ]
                    );
                    return response()->json([
                        'payment_intent_id' => $existing->id,
                        'client_secret' => $existing->client_secret,
                        'status' => $existing->status,
                        'amount' => $amount,
                        'currency' => $currency,
                    ]);
                }
            } catch (\Throwable $e) {
                // continuar con creación
            }
        }

        $params = [
            'amount' => $amount,
            'currency' => strtolower($currency),
//            'customer' => $user->stripe_id,
            'description' => $description,
            'automatic_payment_methods' => ['enabled' => true],
            'receipt_email' => (string) $user->email,
            'metadata' => [
                'user_id' => (string) $user->id,
                'app_email' => (string) $user->email,
                'purpose' => (string) $purpose,
                'plan' => $planSlug,
                'plan_sku' => $planSlug,
                'plan_label' => (string) ($planDefinition['name'] ?? $planSlug),
                'period' => (string) $data['period'],
                'variant' => 'intro',
                'current_plan' => $user->planSlug(),
            ],
            'setup_future_usage' => 'off_session',
        ];

        $pi = $stripe->paymentIntents->create($params, [
            'idempotency_key' => $intentKey . ':' . Str::uuid(),
        ]);

        Cache::put($intentKey, ['id' => $pi->id], now()->addMinutes(15));

        Payment::updateOrCreate(
            ['stripe_payment_intent_id' => $pi->id],
            [
                'user_id' => $user->id,
                'amount' => $amount,
                'currency' => $currency,
                'status' => $pi->status,
            ]
        );

        return response()->json([
            'payment_intent_id' => $pi->id,
            'client_secret' => $pi->client_secret,
            'status' => $pi->status,
            'amount' => $amount,
            'currency' => $currency,
        ]);
    }

    protected function ensurePlanPurchaseAllowed(User $user, string $planSlug): array
    {
        $plans = config('billing.plans', []);

        abort_unless(isset($plans[$planSlug]), 422, 'Plan inválido.');
        abort_if($planSlug === User::PLAN_FREE, 422, 'El plan gratuito no requiere pago.');
        abort_if($user->planSlug() === $planSlug, 409, 'Ya cuentas con este plan.');

        return $plans[$planSlug];
    }
}
