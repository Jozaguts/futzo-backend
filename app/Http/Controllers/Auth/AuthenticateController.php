<?php

namespace App\Http\Controllers\Auth;
use App\Http\Requests\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthenticateController extends Controller
{
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

    public function login(Request $request)
    {

        $request->validate([
            'email' => 'required',
            'password'=> 'required'
        ],$request->only('email', 'password'));

        $user = User::where('email', $request->email)->first();
        if(!$user || !Hash::check($request->password, $user->password)){
            return response()->json(['success' => false, 'message' => 'Invalid credentials'], 401);
        }
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json(['success' => true, 'token' => $token]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['success' => true]);
    }

    public function me(Request $request)
    {
        return new UserResource(User::find($request->user()->id));
    }
}
