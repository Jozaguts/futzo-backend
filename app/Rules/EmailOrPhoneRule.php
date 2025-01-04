<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EmailOrPhoneRule implements ValidationRule
{
	public function validate(string $attribute, mixed $value, Closure $fail): void
	{
		if (User::where('email', $value)->orWhere('phone', $value)->exists()) {
			$fail('El :attribute ya ha sido registrado.');
		}
	}
}
