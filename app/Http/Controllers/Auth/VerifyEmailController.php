<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\VerifyEmailRequest;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(VerifyEmailRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::where('verification_token', $data['code'])
            ->where(function ($query) use ($data) {
                if (isset($data['phone'])) {
                    $query->where('phone', $data['phone']);
                } else {
                    $query->where('email', $data['email']);
                }
            })->firstOrFail();

        $this->markEmailAsVerified($user);

        event(new Verified($user));

        return response()->json(['message' => 'Cuenta verificada exitosamente.']);
    }

    private function markEmailAsVerified(User $user): void
    {
        $user->update([
            'verified_at' => $user->freshTimestamp(),
        ]);
    }
}
