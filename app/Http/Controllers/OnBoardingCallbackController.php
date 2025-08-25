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
    /**
     * @throws ApiErrorException
     */
    public function __invoke(Request $request)
    {
        $sessionId = $request->query('session_id');
        abort_unless($sessionId, 400);
        $stripe = new StripeClient(config('services.stripe.secret'));
        $s = $stripe->checkout->sessions->retrieve($sessionId);
        abort_unless($s->payment_status === 'paid', 402, 'Payment not completed');

        $row = PostCheckoutLogin::where('checkout_session_id', $sessionId)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();
        if (!$row) {
            $email = $s->customer_details?->email;
            if ($email) {
                $user = User::withoutEvents(static function () use ($email) {
                    return User::firstOrCreate(['email' => $email], [
                        'name' => Str::before($email, '@'),
                    ]);
                });
                $row = PostCheckoutLogin::create([
                    'checkout_session_id' => $sessionId,
                    'user_id' => $user->id,
                    'expires_at' => now()->addMinutes(30),
                ]);
            }
        }
        abort_unless($row, 404);

        // One-time
        $row->update(['used_at' => now()]);

        auth()->loginUsingId($row->user_id);

        // Redirige a tu vista de bienvenida (wizard de liga)
        return redirect()->away(config('app.frontend_url'). '/bienvenido');
    }
}
