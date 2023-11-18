<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PenaltyGoalKeeperUpdateRequest extends FormRequest
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
            'game_id' => 'integer|exists:games,id',
            'team_id' => 'integer|exists:teams,id',
            'player_id' => 'integer|exists:players,id',
        ];
    }
}
