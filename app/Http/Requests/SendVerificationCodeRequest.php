<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReSendVerificationCodeRequest extends FormRequest
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
}
