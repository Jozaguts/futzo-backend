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
            'tags' => 'array',
            'autocomplete_prediction.place_id' => [
                'required',
                'string',
                'autocomplete_prediction.place_id' => [
                    'required',
                    'string',
                ],
                function ($attribute, $value, $fail) {
                    $exists = \App\Models\Location::whereJsonContains('autocomplete_prediction->place_id', $value)->exists();
                    \App\Models\Location::whereJsonContains('autocomplete_prediction->place_id', $value)->exists();
                    if ($exists) {
                        $fail("Esta ubicación ya está registrada.");
                    }
                },
            ],
        ];
    }
}
