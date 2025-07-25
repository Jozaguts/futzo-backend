<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubstitutionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'game_id' => ['required', 'exists:games'],
            'team_id' => ['required', 'exists:teams'],
            'player_in_id' => ['required', 'exists:players'],
            'player_out_id' => ['required', 'exists:players'],
            'minute' => ['required', 'integer'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
