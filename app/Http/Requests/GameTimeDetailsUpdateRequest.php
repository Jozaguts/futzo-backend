<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GameTimeDetailsUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'game_id' => 'required|exists:games,id',
            'first_time_start' => 'date_format:H:i',
            'first_time_end' => 'date_format:H:i',
            'second_time_start' => 'date_format:H:i',
            'second_time_end' => 'date_format:H:i',
            'prorogue_minutes_start' => 'date_format:i',
            'first_time_extra_time' => 'date_format:i',
            'second_time_extra_time' => 'date_format:i',
        ];
    }
}
