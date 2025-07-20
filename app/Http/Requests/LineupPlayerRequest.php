<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LineupPlayerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'lineup_id' => ['required', 'exists:lineups'],
            'player_id' => ['required', 'exists:players'],
            'field_location' => ['nullable', 'integer'],
            'substituted' => ['boolean'],
            'goals' => ['required', 'integer'],
            'yellow_card' => ['boolean'],
            'red_card' => ['boolean'],
            'doble_yellow_card' => ['boolean'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
