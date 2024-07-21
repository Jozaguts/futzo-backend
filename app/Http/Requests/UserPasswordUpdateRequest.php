<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserPasswordUpdateRequest extends FormRequest
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
            'password' => ['required', 'current_password:sanctum'],
            'new_password' => ['required', 'string', 'min:8','confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.current_password' => 'La contraseña actual no es correcta',
            'new_password.confirmed' => 'Las contraseñas no coinciden',
            'new_password.min' => 'La nueva contraseña debe tener al menos 8 caracteres',
            'new_password.required' => 'La nueva contraseña es requerida',
            'password.required' => 'La contraseña actual es requerida',
        ];
    }
}
