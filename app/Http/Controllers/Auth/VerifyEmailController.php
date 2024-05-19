<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(Request $request): \Illuminate\Http\JsonResponse
    {
        $token = $request->query('token');
        $message = '';
        $user = User::where('email_verification_token', $token)->first();


        if (!$user) {
            $message = 'Invalid token.';
            return response()->json(['message' => $message], 400);
        }

        if ($this->hasVerifiedEmail($user)) {
            $message = 'Email already verified.';
            return response()->json(['message' => $message], 400);
        }

        if ($this->markEmailAsVerified($user)) {
            event(new Verified($user));
        }
        $message = 'Email verified successfully.';
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
