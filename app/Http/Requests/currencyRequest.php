<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class currencyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required'],
            'symbol' => ['required'],
            'iso_code' => ['required'],
            'payment_gateway' => ['required'],
            'is_default' => ['boolean'],
            'properties' => ['required'],
            'usd_rate_exchange' => ['required'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
