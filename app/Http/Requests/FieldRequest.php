<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FieldRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'location_id' => ['required', 'exists:locations'],
            'name' => ['required'],
            'type' => ['required'],
            'dimensions' => ['required'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
