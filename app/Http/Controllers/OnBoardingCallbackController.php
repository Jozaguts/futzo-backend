<?php

namespace App\Http\Controllers;

use App\Jobs\SendMetaCapiEventJob;
use App\Models\PostCheckoutLogin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class OnBoardingCallbackController extends Controller
{
    const int PURCHASE_SUBSCRIPTION_CODE = 2000;
    /**
     * @throws ApiErrorException
     */
    public function __invoke(Request $request)
    {
        $sessionId = $request->query('session_id');
        abort_unless($sessionId, 400);

        $stripe = new StripeClient(config('services.stripe.secret'));
        $s = $stripe->checkout->sessions->retrieve($sessionId,[
            'expand' => ['subscription'],
        ]);
        $email =  $s->customer_details['email'];
        $phone = $s->subscription->metadata['app_phone'];

        abort_unless($s->payment_status === 'paid', 402, 'Payment not completed');
        $user = User::where('email', $email)
            ->orWhere('phone',$phone)->first();
        $eventId =  Str::uuid();
        $userCtx = [
            'external_id' => (string) $user?->id,
            'ip'          => $request->ip(),
            'ua'          => $request->userAgent(),
            // Tomados de los valores persistidos en el usuario (capturados al registrar/login)
            'fbp'         => $user?->fbp,
            'fbc'         => $user?->fbc,
            'fbclid'      => $user?->fbclid,
        ];
        if (!is_null($phone)){
            $userCtx['phone'] = $phone;
        }
        if (!is_null($email)){
            $userCtx['email'] = $email;
        }

        // Solo enviar a Meta si tenemos tokens de FB (evita atribuir a Facebook cuando no lo es)
        $hasFbAttribution = !empty($user?->fbp) || !empty($user?->fbc) || !empty($user?->fbclid);
        if ($hasFbAttribution) {
            $amountTotal = ($s->amount_total ?? 0);
            $currency    = strtoupper($s->currency ?? 'MXN');
            $postTrial   = $user?->trial_ends_at && now()->greaterThan($user->trial_ends_at);
            SendMetaCapiEventJob::dispatch(
                eventName: 'Purchase',
                eventId: $eventId,
                userCtx: $userCtx,
                custom: [
                    'value'       => $amountTotal > 0 ? round($amountTotal / 100, 2) : 0,
                    'currency'    => $currency,
                    'is_post_trial' => (bool) $postTrial,
                ],
                eventSourceUrl: config('app.url') . '/configuracion?payment=success',
                testCode: $request->input('test_event_code'),
                actionSource: 'website',
                consent: $request->boolean('consent', true)
            );
        }
        return redirect()->away(
            config('app.frontend_url') . "/configuracion?payment=success&plan=kicjoff"
        );
    }
}
