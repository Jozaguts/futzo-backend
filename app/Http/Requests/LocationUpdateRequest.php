<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LocationUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'city' => 'required|string',
            'address' => 'required|string',
            'autocomplete_prediction' => [
                'required',
                'array',
            ],
            'tags' => 'array',
            'availability' => 'array',
            'position' => 'required|array',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es requerido.',
            'name.string' => 'El nombre debe ser una cadena de texto.',
        ];
    }
}
