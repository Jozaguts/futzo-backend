<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LineupUpdateRequest extends FormRequest
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
            'game_id' => 'exists:games,id',
            'player_id' => 'exists:players,id',
            'first_team_player' => 'boolean',
            'round' => 'string'
        ];
    }
}
