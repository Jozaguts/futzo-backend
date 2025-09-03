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
        $user  =  $request->user();
        $ownerKey = $user->id;

        $intentKey = "checkout:intent:{$ownerKey}:{$plan}:{$period}";
        $lockKey   = "lock:{$intentKey}";
        return Cache::lock($lockKey, 10)->block(5, function () use ($plan, $period, $user, $intentKey) {
            // 2. si existe un intento de pago del mismo plan, email y periodo se obtiene desde el caché
            $cached = Cache::get($intentKey);
            if ($cached && isset($cached['session_id'])) {
                logger('cached session id: ' . $cached['session_id']);
                try {
                    //obtenemos el último session creada por el user
                    $s = $user->stripe()->checkout->sessions->retrieve($cached['session_id']);
                    // si la session sigue open y no ha expirado la retornamos
                    if ($s->status === 'open' && ($s->expires_at ?? 0) > now()->timestamp) {
                        return response()->json(['url' => $cached['url']]);
                    }
                } catch (\Throwable $e) {

                }
                // si ya expiro o no está en status open la borramos del cache
                Cache::forget($intentKey);
                // mismo para el session_id del cache
                if (!empty($cached['session_id'])) {
                    Cache::forget("checkout:intent:by_session:{$cached['session_id']}");
                }
            }
            // 3. en este punto se validó que el user no ha intentado crear un payment intent de Stripe

            // 4. validamos si alguna vez tuvo una subscription
            $hasSubscriptions = $user->subscriptions()->count() > 0;
            // 5. Si es primera compra se aplica el special_first_month price caso contrario intro/regular price
            $price = !$hasSubscriptions && $period == 'month'
                ? ProductPrice::for($plan, 'special_first_month', 'month') // primer mes
                : ProductPrice::for($plan, 'intro', $period); // intro mensual/anual
            abort_unless($price?->stripe_price_id, 422, 'Price not configured');
            $success = route('billing.callback').'?session_id={CHECKOUT_SESSION_ID}';
            $cancel  = config('app.frontend_url'). '/suscripcion?cancel=1';
            // 6. se crea el checkout la subscription todavía no existe en base de datos
            $session = $user->newSubscription('default', $price?->stripe_price_id)
                ->withMetadata([
                    'plan_sku'  => (string)$plan,
                    'period'    => (string)$period,
                    'app_email' => (string)$user->email,
                    'first_purchase' => !$hasSubscriptions ? '1' : '0',
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
