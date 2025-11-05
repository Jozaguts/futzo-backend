<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TeamHomePreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'home_location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'home_day_of_week' => [
                'nullable',
                'integer',
                'min:0',
                'max:6',
                'required_with:home_location_id',
            ],
            'home_start_time' => [
                'nullable',
                'date_format:H:i',
                'required_with:home_location_id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'home_day_of_week.required_with' => 'Selecciona un día cuando asignas una sede.',
            'home_start_time.required_with' => 'Selecciona un horario cuando asignas una sede.',
        ];
    }
}

