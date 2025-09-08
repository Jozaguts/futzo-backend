<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class VerifyEmailRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => 'required|string|min:4',
            'email' => 'required_without:phone|email|exists:users,email',
            'phone' => 'required_without:email|regex:/^\+?[1-9]\d{1,14}$/|exists:users,phone',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $data = $this->validated();

            $user = User::where(static function ($q) use ($data) {
                if (!empty($data['email'])) {
                    $q->where('email', $data['email']);
                } elseif (!empty($data['phone'])) {
                    $q->where('phone', $data['phone']);
                }
            })->first();

            // Permitir OTP fijo en entornos locales/de prueba para dominios de test
            $allowTestOtp = app()->environment(['local', 'testing', 'staging'])
                && !empty($data['email'])
                && str_ends_with(strtolower($data['email']), '@playwright.test')
                && ((string)($data['code'] ?? '')) === '1111';

            if (!$user || ($user->verification_token !== $data['code'] && !empty($data['email']) && !$allowTestOtp)) {
                $validator->errors()->add('code', 'El código es inválido para este usuario.');
                return;
            }

            if ($user->verified_at !== null) {
                $validator->errors()->add('code', 'Este código ya fue utilizado.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'code.required' => 'El código de verificación es requerido.',
            'code.exists' => 'Código de verificación inválido.',
            'email.required' => 'El correo electrónico es requerido.',
            'email.email' => 'El correo electrónico no es válido.',
            'email.exists' => 'Correo electrónico no registrado.'
        ];
    }
}
