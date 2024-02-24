<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PenaltyUpdateRequest extends FormRequest
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
            'match_id'=> 'integer|exists:matches,id',
            'team_id'=> 'integer|exists:teams,id',
            'player_id'=> 'integer|exists:players,id',
            'score_goal'=> 'integer',
            'kicks_number'=> 'integer',
        ];
    }
}
