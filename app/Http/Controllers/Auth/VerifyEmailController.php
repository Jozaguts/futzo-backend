<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\VerifyEmailRequest;
use App\Models\User;
use Illuminate\Auth\Events\Verified;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(VerifyEmailRequest $request): \Illuminate\Http\JsonResponse
    {
        $code = $request->validated()['code'];

        $user = User::where('email_verification_token', $code)->first();


        if (!$user) {
            $message = 'Código de verificación inválido.';
            return response()->json(['message' => $message], 400);
        }

        if ($this->hasVerifiedEmail($user)) {
            $message = 'Correo electrónico ya ha sido verificado.';
            return response()->json(['message' => $message], 400);
        }

        if ($this->markEmailAsVerified($user)) {
            event(new Verified($user));
        }
        $message = 'Correo electrónico verificado correctamente.';
        return response()->json(['message' => $message]);
    }
    private function hasVerifiedEmail($user): bool
    {
        return ! is_null($user->email_verified_at);
    }
    private function markEmailAsVerified($user): bool
    {
        return $user->forceFill([
            'email_verified_at' => $user->freshTimestamp(),
        ])->save();
    }
}
