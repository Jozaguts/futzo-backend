<?php

namespace App\Http\Controllers;

use App\Models\ProductPrice;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionIntentController extends Controller
{
    private const BLOCKING_STRIPE_STATUSES = ['trialing','active','past_due','unpaid','paused','processing'];
    private const RETRYABLE_STRIPE_STATUSES = ['incomplete','incomplete_expired'];
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
        $planSlug = (string) $data['plan'];
        $planDefinition = $this->ensurePlanPurchaseAllowed($user, $planSlug);

        $price = ProductPrice::for($planSlug, 'intro', $data['period']);
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

        // Limpiar suscripciones locales pendientes/incompletas
        $this->cleanupPendingLocalSubscriptions($user);

        $hasBlockingLocalSub = $this->hasBlockingLocalSubscription($user);
        $hasBlockingRemoteSub = $this->hasBlockingRemoteSubscription($user);

        if ($hasBlockingLocalSub || $hasBlockingRemoteSub) {
            // Generar URL del billing portal para gestionar su plan existente
            $returnUrl = config('app.frontend_url') . '/configuracion';
            $session = $user->stripe()->billingPortal->sessions->create([
                'customer' => $user->stripe_id,
                'return_url' => $returnUrl,
            ]);
            return response()->json([
                'error' => 'already_has_subscription',
                'message' => 'Ya tienes una suscripción activa o pendiente. Adminístrala desde el portal de facturación.',
                'billing_portal_url' => $session->url,
            ], 409);
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
                'plan' => $planSlug,
                'plan_sku' => $planSlug,
                'plan_label' => (string) ($planDefinition['name'] ?? $planSlug),
                'period' => (string) $data['period'],
                'variant' => 'intro',
                'user_id' => (string) $user->id,
                'app_user_id' => (string) $user->id,
                'app_email' => (string) $user->email,
                'current_plan' => $user->planSlug(),
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

    protected function ensurePlanPurchaseAllowed(User $user, string $planSlug): array
    {
        $plans = config('billing.plans', []);

        abort_unless(isset($plans[$planSlug]), 422, 'Plan inválido.');
        abort_if($planSlug === User::PLAN_FREE, 422, 'El plan gratuito no requiere pago.');
        abort_if($user->planSlug() === $planSlug, 409, 'Ya cuentas con este plan.');

        return $plans[$planSlug];
    }

    protected function hasBlockingLocalSubscription(User $user): bool
    {
        return $user->subscriptions()
            ->whereNull('ends_at')
            ->where(function ($query) {
                $query->whereIn('stripe_status', self::BLOCKING_STRIPE_STATUSES)
                    ->orWhereNull('stripe_status');
            })
            ->exists();
    }

    protected function hasBlockingRemoteSubscription(User $user): bool
    {
        if (!$user->stripe_id) {
            return false;
        }

        $pendingToCancel = [];
        try {
            $remoteSubs = $user->stripe()->subscriptions->all([
                'customer' => $user->stripe_id,
                'status' => 'all',
                'limit' => 10,
            ]);
            foreach ($remoteSubs->data as $subscription) {
                if (in_array($subscription->status, self::BLOCKING_STRIPE_STATUSES, true)) {
                    return true;
                }
                if (in_array($subscription->status, self::RETRYABLE_STRIPE_STATUSES, true)) {
                    $pendingToCancel[] = $subscription->id;
                }
            }
        } catch (\Throwable $e) {
            return false;
        }

        foreach ($pendingToCancel as $subscriptionId) {
            $this->cancelStripeSubscription($user, $subscriptionId);
        }

        return false;
    }

    protected function cleanupPendingLocalSubscriptions(User $user): void
    {
        $user->subscriptions()
            ->whereNull('ends_at')
            ->whereIn('stripe_status', self::RETRYABLE_STRIPE_STATUSES)
            ->each(function ($subscription) use ($user) {
                if (!empty($subscription->stripe_id)) {
                    $this->cancelStripeSubscription($user, $subscription->stripe_id);
                } else {
                    $subscription->delete();
                }
            });
    }

    protected function cancelStripeSubscription(User $user, ?string $stripeSubscriptionId): void
    {
        if (!$stripeSubscriptionId) {
            return;
        }

        try {
            $user->stripe()->subscriptions->cancel($stripeSubscriptionId, [
                'invoice_now' => false,
                'prorate' => false,
            ]);
        } catch (\Throwable $e) {
            // ignore failures; maybe already cancelled
        }

        $user->subscriptions()->where('stripe_id', $stripeSubscriptionId)->delete();
    }
}
