<?php

namespace App\Http\Controllers;

use App\Models\PostCheckoutLogin;
use Illuminate\Http\Request;

class PostCheckoutLoginController extends Controller
{
    public function __invoke(Request $request)
    {
        logger('PostCheckoutLoginController');
        $token = $request->input('token');

        abort_unless($token, 400, 'Token missing');

        $row = PostCheckoutLogin::where('login_token',hash('sha256',$token))
            ->whereNull('used_at')
            ->where('expires_at','>',now())
            ->first();

        abort_unless((bool)$row,401, 'Token invalido o expirado');

        $row?->update(['used_at' => now()]);

        auth()->loginUsingId($row?->user_id);
        return response()->json([
            'status' => 'ok',
            'message' => 'Autenticación exitosa'
        ]);
    }
}
