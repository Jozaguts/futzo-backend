<?php

namespace app\Http\Controllers\Auth;
use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;

class AuthenticateController extends Controller
{
    public function register(StoreUserRequest $request)
    {
        $validated = $request->validated();
        if(!$validated){
            return response()->json(['success' => false, 'message' => 'Invalid credentials'], 401);
        }
        $user = new User();
        $user->email = $request->email;
        $plainPassword = $request->password;
        $user->password = bcrypt($plainPassword);
        $user->save();
        $token = $user->createToken('auth_token')->plainTextToken;

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
}
