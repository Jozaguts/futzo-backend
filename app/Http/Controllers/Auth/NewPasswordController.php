<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class NewPasswordController extends Controller
{
    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required_without:phone', 'email', 'exists:users,email'],
            'phone' => ['required_without:email', 'regex:/^\+?[1-9]\d{1,14}$/', 'exists:users,phone'],
            'password' => ['required', Rules\Password::defaults()],
        ]);

        $isPhone = $request->has('phone');
        if ($isPhone) {
            $user = User::where('phone', trim($request->phone))->firstOrFail();

            if ($user->verified_at === null) {
                throw ValidationException::withMessages([
                    'token' => ['Debes verificar el código antes de cambiar la contraseña.'],
                ]);
            }
            $record = DB::table('phone_password_resets')
                ->where('phone', $request->phone)
                ->where('token', $request->token)
                ->first();

            if (!$record || Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
                throw ValidationException::withMessages([
                    'token' => ['El código es inválido o ha expirado.'],
                ]);
            }

            $user->forceFill([
                'password' => $request->password,
                'remember_token' => Str::random(60),
            ])->save();

            event(new PasswordReset($user));
            DB::table('phone_password_resets')->where('phone', $request->phone)->delete();

            return response()->json(['status' => 'Contraseña actualizada con éxito', 'code' => 200]);
        }
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            static function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status != Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json(['status' => __($status), 'code' => 200]);
    }
}
