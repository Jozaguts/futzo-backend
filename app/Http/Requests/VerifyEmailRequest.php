<?php

namespace App\Http\Requests;

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
			'code' => 'required|exists:users,verification_token',
			'email' => 'required_without:phone|email|exists:users,email',
			'phone' => 'required_without:email|regex:/^\+?[1-9]\d{1,14}$/|exists:users,phone',
		];
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
