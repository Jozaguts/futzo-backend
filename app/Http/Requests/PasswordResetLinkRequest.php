<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PasswordResetLinkRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required_without:phone|email|exists:users,email',
            'phone' => 'required_without:email|regex:/^\+?[1-9]\d{1,14}$/|exists:users,phone',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function messages()
    {
        return [
            'email.exists' => 'El correo electrónico seleccionado no es válido.',
            'phone.exists' => 'El número de teléfono seleccionado no es válido.',
        ];
    }
}
