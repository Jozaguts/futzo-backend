<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionIntentController extends Controller
{
    /**
     * Crea una suscripción en estado incomplete (payment_behavior=default_incomplete)
     * y retorna el client_secret del PaymentIntent del último invoice para usar
     * con Stripe Payment Element (modo suscripción).
     *
     * Request JSON:
     *  - plan: sku del plan (p.ej. "kickoff")
     *  - period: 'month' | 'year'
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

        // Asegurar y sincronizar cliente en Stripe (prefill de email en Payment Element)
        $user->createOrGetStripeCustomer();
        try {
            $user->updateStripeCustomer([
                'email' => (string) $user->email,
                'name'  => trim($user->name . ' ' . ($user->last_name ?? '')),
                'phone' => $user->phone,
            ]);
        } catch (\Throwable $e) {
            // si falla, continuamos; el Payment Element leerá del intent de todos modos
        }

        // Crear suscripción en estado incomplete para confirmar con Payment Element
        $stripe = $user->stripe();
        $sub = $stripe->subscriptions->create([
            'customer' => $user->stripe_id,
            'items' => [[ 'price' => $price->stripe_price_id ]],
            'payment_behavior' => 'default_incomplete',
            'payment_settings' => [
                'save_default_payment_method' => 'on_subscription',
            ],
            'expand' => ['latest_invoice.payment_intent'],
            'metadata' => [
                'plan' => (string) $data['plan'],
                'period' => (string) $data['period'],
                'variant' => 'intro',
                'app_user_id' => (string) $user->id,
                'app_email' => (string) $user->email,
            ],
        ]);

        $invoice = $sub->latest_invoice; // expanded
        $pi = $invoice?->payment_intent;
        abort_unless($pi && isset($pi->client_secret), 500, 'No se pudo crear PaymentIntent para la suscripción');

        return response()->json([
            'subscription_id' => $sub->id,
            'payment_intent_id' => $pi->id,
            'client_secret' => $pi->client_secret,
            'amount' => $pi->amount ?? null,
            'currency' => strtoupper($pi->currency ?? 'MXN'),
            'status' => $pi->status,
        ]);
    }
}
