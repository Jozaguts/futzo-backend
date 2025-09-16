<?php

namespace App\Http\Controllers\Auth;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthenticateController extends Controller
{
	public function register(StoreUserRequest $request): JsonResponse
	{
		$validated = $request->validated();

		try {
			DB::beginTransaction();
			User::create($validated);
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

        // Si llegan tokens de Meta al iniciar sesión, los persistimos para usarlos más adelante
        /** @var User $user */
        $user = $request->user();
        if ($user) {
            $updated = false;
            foreach (['fbp','fbc','fbclid'] as $k) {
                $v = $request->input($k);
                if ($v && empty($user->{$k})) {
                    $user->{$k} = $v;
                    $updated = true;
                }
            }
            if ($request->has('consent')) {
                $user->capi_consent = $request->boolean('consent');
                $updated = true;
            }
            if ($updated) {
                $user->save();
            }
        }

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
