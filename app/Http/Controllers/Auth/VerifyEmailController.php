<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\VerifyEmailRequest;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Auth;

class VerifyEmailController extends Controller
{
	/**
	 * Mark the authenticated user's email address as verified.
	 */
	public function __invoke(VerifyEmailRequest $request): \Illuminate\Http\JsonResponse
	{
		$data = $request->validated();
		$isPhone = isset($data['phone']);
		$user = User::query()
			->where([
				'verification_token' => $data['code'],
				$isPhone ? 'phone' : 'email' => $data[$isPhone ? 'phone' : 'email']
			])
			->first();

		if ($this->hasVerifiedEmail($user)) {
			$message = 'Correo electrónico ya ha sido verificado.';
			return response()->json(['message' => $message], 401);
		}

		if ($this->markEmailAsVerified($user)) {
			event(new Verified($user));
		}
		$message = 'Correo electrónico verificado correctamente.';
		// generate cookie session and login user
		Auth::login($user);
		return response()->json(['message' => $message]);
	}

	private function hasVerifiedEmail($user): bool
	{
		return !is_null($user->verified_at);
	}

	private function markEmailAsVerified($user): bool
	{
		return $user->forceFill([
			'verified_at' => $user->freshTimestamp(),
		])->save();
	}
}
