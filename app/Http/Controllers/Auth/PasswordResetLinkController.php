<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Random\RandomException;

class PasswordResetLinkController extends Controller
{
    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws RandomException
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required_without:phone|email|exists:users,email',
            'phone' => 'required_without:email|regex:/^\+?[1-9]\d{1,14}$/|exists:users,phone',
        ]);
        $isPhone = $request->has('phone');
        if ($isPhone) {
            // Lógica para phone
            $phone = $request->phone;
            $token = random_int(1000, 9999);

            DB::table('phone_password_resets')->updateOrInsert(
                ['phone' => $phone],
                ['token' => $token, 'created_at' => now()]
            );

            // Aquí deberías integrar el envío real de SMS
            logger("Token enviado a $phone: $token");

            return response()->json(['message' => 'Código enviado por SMS']);
        }
        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $request->only($isPhone ? 'phone' : 'email')
        );

        if ($status != Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json(['status' => __($status)]);
    }

    public function resetWithPhoneToken(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|regex:/^\+?[1-9]\d{1,14}$/|exists:users,phone',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $record = DB::table('phone_password_resets')
            ->where('phone', $request->phone)
            ->where('token', $request->token)
            ->first();

        if (!$record || Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
            return response()->json(['message' => 'Token inválido o expirado'], 422);
        }

        $user = User::where('phone', $request->phone)->first();
        $user->password = bcrypt($request->password);
        $user->save();

        DB::table('phone_password_resets')->where('phone', $request->phone)->delete();

        return response()->json(['message' => 'Contraseña actualizada con éxito']);
    }
}
