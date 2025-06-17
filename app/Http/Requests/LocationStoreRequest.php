<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LocationStoreRequest extends FormRequest
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
            'name' => 'required|string',
            'city' => 'required|string',
            'address' => 'required|string',
            'autocomplete_prediction' => [
                'required',
                'array',
            ],
            'position' => 'required|array',
            'autocomplete_prediction.description' => 'required|string',
            'autocomplete_prediction.structured_formatting' => 'array',
            'autocomplete_prediction.terms' => 'array',
            'autocomplete_prediction.types' => 'array',
            'autocomplete_prediction.matched_substrings' => 'array',
            'autocomplete_prediction.structured_formatting.main_text' => 'string',
            'autocomplete_prediction.structured_formatting.main_text_matched_substrings' => 'array',
            'autocomplete_prediction.structured_formatting.secondary_text' => 'string',
            'tags' => 'array',
            'autocomplete_prediction.place_id' => [
                'required',
                'string',
//                function ($attribute, $value, $fail) {
//                    $exists = \App\Models\Location::whereJsonContains('autocomplete_prediction->place_id', $value)->exists();
//                    \App\Models\Location::whereJsonContains('autocomplete_prediction->place_id', $value)->exists();
//                    if ($exists) {
//                        $fail("Esta ubicación ya está registrada.");
//                    }
//                },
            ],
            'availability' => 'array',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es requerido.',
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.unique' => 'El nombre de la ubicación ya está en uso.',
        ];
    }
}
