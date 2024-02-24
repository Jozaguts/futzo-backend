<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LineupStoreRequest extends FormRequest
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
            'player_id' => 'required|exists:players,id',
            'first_team_player' => 'boolean|required',
            'round' => 'string|required'
        ];
    }
}
