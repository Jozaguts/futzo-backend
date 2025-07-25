<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GameEventRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'game_id' => ['required', 'exists:games'],
            'player_id' => ['required', 'exists:players'],
            'related_player_id' => ['required', 'exists:players'],
            'team_id' => ['required', 'exists:teams'],
            'minute' => ['required', 'integer'],
            'type' => ['required'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
