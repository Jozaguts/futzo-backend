<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReSendVerificationCodeRequest extends FormRequest
{
	public function rules(): array
	{
		return [
			'email' => 'email|required|exists:users,email',
		];
	}

	public function authorize(): bool
	{
		return true;
	}
}
