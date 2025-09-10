<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LocationUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'address' => 'required|string',
            'tags' => 'array',
            'fields' => 'array',
            'fields.*.id' => 'nullable|integer',
            'fields.*.name' => 'required_with:fields|string',
            'fields.*.windows' => 'array',
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
