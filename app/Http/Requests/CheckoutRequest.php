<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'plan'   => 'required|in:kickoff,pro_play,elite_league',
            'period' => 'required|in:month,year',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
