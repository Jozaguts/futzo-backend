<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
	private mixed $column;

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
	 * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
	 */
	public function rules(): array
	{
		return [
			'email' => 'required_without:phone|email|exists:users,email',
			'phone' => 'required_without:email|regex:/^\+?[1-9]\d{1,14}$/|exists:users,phone',
			'password' => ['required', 'string'],
		];
	}

	/**
	 * Attempt to authenticate the request's credentials.
	 *
	 * @throws \Illuminate\Validation\ValidationException
	 * @throws \JsonException
	 */
	public function authenticate(): void
	{
		$content = json_decode($this->content, false, 512, JSON_THROW_ON_ERROR);
		$this->column = isset($content->email) ? 'email' : 'phone';
		$user = User::where($this->column, $this->input($this->column))->first();

		if ($user && !$user->hasVerifiedEmail()) {
			throw ValidationException::withMessages([
				$this->column => $this->column === 'email' ? __('auth.verify') : __('auth.verify_phone'),
			]);
		}

		$this->ensureIsNotRateLimited();

		if (!Auth::attempt($this->only($this->column, 'password'), $this->boolean('remember'))) {
			RateLimiter::hit($this->throttleKey());

			throw ValidationException::withMessages([
				$this->column => __('auth.failed'),
			]);
		}

		RateLimiter::clear($this->throttleKey());
	}

	/**
	 * Ensure the login request is not rate limited.
	 *
	 * @throws \Illuminate\Validation\ValidationException
	 */
	public function ensureIsNotRateLimited(): void
	{
		if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
			return;
		}

		event(new Lockout($this));

		$seconds = RateLimiter::availableIn($this->throttleKey());

		throw ValidationException::withMessages([
			$this->column => trans('auth.throttle', [
				'seconds' => $seconds,
				'minutes' => ceil($seconds / 60),
			]),
		]);
	}

	/**
	 * Get the rate limiting throttle key for the request.
	 */
	public function throttleKey(): string
	{
		return Str::transliterate(Str::lower($this->input($this->column)) . '|' . $this->ip());
	}
}
