<?php

namespace App\Http\Controllers\Auth;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
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
            $user = User::create($validated);
            $user->assignRole('admin');

            $token = $user->createToken('auth_token')->plainTextToken;
            DB::commit();
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 401);
        }

        return response()->json(['success' => true, 'token' => $token]);
    }

    public function login(LoginRequest $request)
    {
        $request->authenticate();

        $request->session()->regenerate();

        return response()->noContent();
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
//        $request->user()->currentAccessToken()->delete();
//        return response()->json(['success' => true]);
    }
}
