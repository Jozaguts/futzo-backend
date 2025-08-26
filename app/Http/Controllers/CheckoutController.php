<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\League;
use App\Models\ProductPrice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class CheckoutController extends Controller
{
    /**
     * @throws LockTimeoutException
     */
    public function __invoke(CheckoutRequest $request)
    {
        $plan   = $request->string('plan');
        $period = $request->string('period'); // 'month'|'year'
        $identifier  = $request->string('identifier');
        $ownerKey = $request->user()
            ? 'user:' . $request->user()->id
            : 'email:' . $identifier;

        $intentKey = "checkout:intent:{$ownerKey}:{$plan}:{$period}";
        $lockKey   = "lock:{$intentKey}";
        return Cache::lock($lockKey, 10)->block(5, function () use ($request, $plan, $period, $identifier, $ownerKey, $intentKey) {
            $user =  User::firstOrCreate(['email' => $identifier], [
                'name' => Str::before($identifier, '@'),
            ]);
//            $stripe = new StripeClient(config('services.stripe.secret'));
            $cached = Cache::get($intentKey);
            if ($cached && isset($cached['session_id'])) {
                try {
                    $s = $user->stripe()->checkout->sessions->retrieve($cached['session_id']);

                    if ($s->status === 'open' && ($s->expires_at ?? 0) > now()->timestamp) {
                        return response()->json(['url' => $cached['url']]);
                    }
                } catch (\Throwable $e) {

                }
                Cache::forget($intentKey);
                if (!empty($cached['session_id'])) {
                    Cache::forget("checkout:intent:by_session:{$cached['session_id']}");
                }
            }
            // se determina si es primera compra
            $existing = User::where('email', $identifier)->first();
            $isFirst = !$existing ||  $existing->subscriptions()->count() > 0;
            //  si es primera compra se aplica el special_first_month price caso contrario intro/regular price
            $price = $isFirst && $period == 'month'
                ? ProductPrice::for($plan, 'special_first_month', 'month') // primer mes
                : ProductPrice::for($plan, 'intro', $period); // intro mensual/anual
            abort_unless($price?->stripe_price_id, 422, 'Price not configured');
            $success = route('billing.callback').'?session_id={CHECKOUT_SESSION_ID}';
            $cancel  = config('app.frontend_url'). '/suscripcion?cancel=1';
            $params = [
                'mode' => 'subscription',
                'line_items' => [['price' => $price?->stripe_price_id, 'quantity' => 1]],
                'success_url' => $success,
                'cancel_url'  => $cancel,
                'allow_promotion_codes' => true,
                'metadata' => [
                    'plan_sku'  => (string)$plan,
                    'period'    => (string)$period,
                    'app_email' => (string)$identifier,
                    'first_purchase' => $isFirst ? '1' : '0',
                    'variant' => (string) $price?->variant
                ],
            ];
            if ($user) {
                $user->createOrGetStripeCustomer();
                $params['customer'] = $user->stripe_id;
                $params['client_reference_id'] = (string)$user->id;
            } else {
                $params['customer_email'] = (string)$identifier;
            }
//            $idempotencyKey = 'chk:' . hash('sha256', "{$ownerKey}|{$plan}|{$period}");

            $session = $user->newSubscription('default',$price?->stripe_price_id)
                ->withMetadata([
                    'plan_sku'  => (string)$plan,
                    'period'    => (string)$period,
                    'app_email' => (string)$identifier,
                    'first_purchase' => $isFirst ? '1' : '0',
                    'variant' => (string) $price?->variant
                ])
                ->checkout([
                    'success_url' => $success,
                    'cancel_url'  => $cancel,
                ]);
            $expiresAtTs = $session->expires_at ?? (now()->addDay()->timestamp);
            $ttlSeconds  = max(60, now()->diffInSeconds(Carbon::createFromTimestamp($expiresAtTs)));
            $payload = [
                'session_id'    => $session->id,
                'url'           => $session->url,
                'expires_at'    => $expiresAtTs,
                'status'        => $session->status,          // open
                'payment_status'=> $session->payment_status,  // unpaid
            ];
            Cache::put($intentKey, $payload, $ttlSeconds);
            Cache::put("checkout:intent:by_session:{$session->id}", $intentKey, $ttlSeconds);
            return response()->json(['url' => $session->url]);
        });
    }
}
