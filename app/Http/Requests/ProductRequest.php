<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest{
    public function rules(): array
    {
        return [
            'name' => ['required'],
            'description' => ['required'],
            'enabled' => ['boolean'],
            'sku' => ['required'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
