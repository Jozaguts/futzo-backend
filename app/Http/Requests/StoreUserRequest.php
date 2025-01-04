<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
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
			'name' => 'required',
			'last_name' => 'nullable',
			'email' => 'required_without:phone|email|unique:users,email',
			'phone' => 'required_without:email|regex:/^\+?[1-9]\d{1,14}$/|unique:users,phone',
			'password' => 'required',
			'image' => 'nullable',
			'league_id' => 'nullable',
		];
	}
}
