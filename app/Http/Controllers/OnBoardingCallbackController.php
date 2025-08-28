<?php

namespace App\Http\Controllers;

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
        $s = $stripe->checkout->sessions->retrieve($sessionId);
        $email =  $s->customer_details['email'];
        $loginToken = Str::random(40); // One-time token set used_at at PostCheckoutLoginController

        abort_unless($s->payment_status === 'paid', 402, 'Payment not completed');
        $user = User::where('email', $email)->first();
        $row = null;
        if ($user) {
            $row =  PostCheckoutLogin::updateOrCreate(
                [
                    'checkout_session_id' => $sessionId,
                    'user_id' => $user->id
                ],
                ['expires_at' => now()->addMinutes(30),
                    'login_token' => hash('sha256',$loginToken)
                ]
            );
        }

        abort_unless((bool)$row, 404);

        return redirect()->away(
            config('app.landing_url') . "/gracias?code=" . self::PURCHASE_SUBSCRIPTION_CODE
            . "&amount_subtotal=" . $s->amount_subtotal
            ."&redirect_url=".urldecode(config('app.frontend_url')."/bienvenido")
            ."&token=".urlencode($loginToken)
        );
    }
}
