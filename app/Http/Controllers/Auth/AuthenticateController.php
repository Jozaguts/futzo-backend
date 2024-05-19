<?php

namespace App\Http\Controllers\Auth;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthenticateController extends Controller
{
    public function __construct()
    {


    }
    public function register(StoreUserRequest $request)
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();
            $validated['email_verification_token'] = str()->random(25);
            $user = User::create($validated);
            $user->assignRole('predeterminado');
            event(new Registered($user));
            DB::commit();
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 401);
        }
    }

    public function login(LoginRequest $request)
    {
        $request->authenticate();

        $request->session()->regenerate();

        return response()->json([
            'message' => 'Login successful'
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
