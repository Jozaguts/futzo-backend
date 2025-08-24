<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\VerifyEmailRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     * @throws TwilioException
     */
    public function __invoke(VerifyEmailRequest $request): JsonResponse
    {
        $phone = $request->input('phone',false);
        $code = $request->input('code',false);
        $email = $request->input('email',false);
        $verified = false;
        $response = ['message' => 'Error al verificar, por favor intente mas tarde', 'user' => null];
        $status = 401;

        if($phone){
            $twilio = new Client(config('services.twilio.sid'), config('services.twilio.token'));
            $check = $twilio->verify->v2->services(config('services.twilio.verify_sid'))
                ->verificationChecks
                ->create([
                    'code' => $code,
                    'to' => $phone,
                ]);
            $verified = $check->status === 'approved';
        } else if($email){
            $verified = User::where('email', $email)
                ->where('verification_token', $code)
                ->exists();
        }
        if ($verified) {
            $user = User::where(static function ($q) use ($phone, $email) {
                if (!empty($email)) {
                    $q->where('email', $email);
                } elseif (!empty($phone)) {
                    $q->where('phone', $phone);
                }
            })->firstOrFail();
            auth()->login($user, true);
            $request->session()->regenerate();
            $this->markEmailAsVerified($user);
            event(new Verified($user));
            $response = ['message' => 'Cuenta verificada exitosamente.', 'user' => new UserResource($user)];
            $status = 200;
        }
        return response()->json($response,$status);
    }

    private function markEmailAsVerified(User $user): void
    {
        $user->update([
            'verified_at' => $user->freshTimestamp(),
        ]);
    }
}
