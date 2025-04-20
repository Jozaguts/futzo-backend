<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\PasswordResetLinkRequest;
use App\Models\User;
use App\Notifications\SendOTPNotification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
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
    public function store(PasswordResetLinkRequest $request): JsonResponse
    {
        $isPhone = $request->has('phone');
        if ($isPhone) {
            $phone = $request->phone;
            $token = random_int(1000, 9999);
            $user = User::where('phone', $phone)->first();
            $user->verification_token = $token;
            $user->save();
            DB::table('phone_password_resets')->updateOrInsert(
                ['phone' => $phone],
                ['token' => $token, 'created_at' => now()]
            );

            Notification::route('whatsapp', $phone)->notify(new SendOTPNotification($token, $token));

            return response()->json(['message' => 'Código enviado por SMS', 'code' => 200]);
        } else {
            
            $status = Password::sendResetLink(
                $request->only($isPhone ? 'phone' : 'email')
            );

            if ($status != Password::RESET_LINK_SENT) {
                throw ValidationException::withMessages([
                    'email' => [__($status)],
                ]);
            }
        }


        return response()->json(['status' => __($status), 'code' => 200]);
    }

    public function resetWithPhoneToken(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|regex:/^\+?[1-9]\d{1,14}$/|exists:users,phone',
            'token' => 'required|string',
            'password' => 'required|string|min:8',
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

    public function verifyResetToken(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|regex:/^\+?[1-9]\d{1,14}$/|exists:users,phone',
            'token' => 'required|string',
        ]);

        $user = User::where('phone', $request->phone)->first();

        if (!$user || $user->verification_token !== $request->token) {
            return response()->json(['message' => 'Código inválido'], 422);
        }

        $user->verification_token = null;
        $user->save();

        return response()->json(['message' => 'Código verificado correctamente', 'code' => 200]);
    }
}
