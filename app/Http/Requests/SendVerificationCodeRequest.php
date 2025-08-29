<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendVerificationCodeRequest extends FormRequest
{
	public function rules(): array
	{
		return [
			'email' => 'required_without:phone|email|exists:users,email',
			'phone' => [
				'required_without:email',
				'regex:/^\+?[1-9]\d{1,14}$/',
				Rule::exists('users', 'phone')->whereNull('verified_at'),
			],
		];
	}

	protected function prepareForValidation()
	{
		if ($this->has('phone')) {
			$this->merge([
				'phone' => '+' . ltrim($this->phone, '+'),
			]);
		}
	}

	public function authorize(): bool
	{
		return true;
	}
    public function messages(): array
    {
        return [
            'phone.regex' => 'Numero de teléfono no valido.',
            'phone.whereNull' => 'Numero teléfono ya ha sido valido',
            'phone.exists' => 'Numero teléfono no coincide en nuestro sistema.',
            'email.required_without' => 'Email es requerido.',
            'email.email' => 'Email debe ser valido.',
            'email.exists' => 'Email es invalido.',

        ];
    }
}
