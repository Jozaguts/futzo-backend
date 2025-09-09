<?php

namespace App\Http\Controllers;

use App\Models\Payment;
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
        $price = \App\Models\ProductPrice::for($data['plan'], 'intro', $data['period']);
        abort_unless($price, 422, 'Plan o periodo inválido');

        $amount = (int) $price->price; // centavos
        $currency = strtoupper($price->currency?->iso_code ?: 'MXN');
        $purpose = 'subscription_checkout_prep';
        $description = sprintf('Pago %s (%s)', $data['plan'], $data['period']);

        // Asegurar y sincronizar stripe customer (para prefill de email)
        $user->createOrGetStripeCustomer();
        try {
            $user->updateStripeCustomer([
                'email' => (string) $user->email,
                'name'  => trim($user->name . ' ' . ($user->last_name ?? '')),
                'phone' => $user->phone,
            ]);
        } catch (\Throwable $e) {}

        // Idempotencia por usuario+plan+periodo (variant intro)
        $intentKey = sprintf('pi:create:%d:plan:%s:%s:intro', $user->id, $data['plan'], $data['period']);
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
            'customer' => $user->stripe_id,
            'description' => $description,
            'automatic_payment_methods' => ['enabled' => true],
            'receipt_email' => (string) $user->email,
            'metadata' => [
                'user_id' => (string) $user->id,
                'app_email' => (string) $user->email,
                'purpose' => (string) $purpose,
                'plan' => (string) $data['plan'],
                'period' => (string) $data['period'],
                'variant' => 'intro',
            ],
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
}
