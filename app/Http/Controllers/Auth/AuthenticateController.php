<?php

namespace App\Http\Controllers\Auth;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\StoreUserRequest;
use App\Jobs\SendMetaCapiEventJob;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthenticateController extends Controller
{
	public function register(StoreUserRequest $request): JsonResponse
	{
		$validated = $request->validated();

		try {
			DB::beginTransaction();
			$validated['verification_token'] = random_int(1000, 9999);
			$validated['image'] = 'https://ui-avatars.com/api/?name=' . $validated['name'] . '&color=9155fd&background=F9FAFB';
			$user = User::create($validated);
			$user->assignRole('predeterminado');
            $user->status = 'pending_onboarding';
            $user->save();
            if (app()->environment('production') || app()->environment('local')) {
                $user->sendEmailVerificationNotification();
                $eventId = $request->input('event_id', (string) Str::uuid());
                $userCtx = [
                    'email'       => $user->email,
                    'external_id' => (string) $user->id,
                    'ip'          => $request->ip(),
                    'ua'          => $request->userAgent(),
                    'fbp'         => $request->input('fbp'),
                    'fbc'         => $request->input('fbc'),
                    'fbclid'      => $request->input('fbclid'),
                ];
                SendMetaCapiEventJob::dispatch(
                    eventName: 'StartTrial',
                    eventId: $eventId,
                    userCtx: $userCtx,
                    custom: ['trial_days'=>7,'value'=>0,'currency'=>'MXN'],
                    eventSourceUrl: config('app.url').'/login',
                    testCode: $request->input('test_event_code'),
                    actionSource: 'website',
                    consent: $request->boolean('consent', true)
                );
            }
			event(new Registered($user));
			DB::commit();
			return response()->json(['success' => true, 'message' => 'User created successfully'], 201);
		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'message' => $e->getMessage()], 401);
		}
	}

	/**
	 * @throws ValidationException
	 */
	public function login(LoginRequest $request): JsonResponse
	{
		$request->authenticate();

		$request->session()->regenerate();

		return response()->json([
			'message' => 'Login successful'
		]);
	}

	public function logout(Request $request): Response
	{
		Auth::guard('web')->logout();

		$request->session()->invalidate();

		$request->session()->regenerateToken();

		return response()->noContent();
	}
}
