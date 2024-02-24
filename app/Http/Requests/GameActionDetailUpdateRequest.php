<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GameActionDetailUpdateRequest extends FormRequest
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
            'action_id' => 'required|exists:actions,id',
            'game_id' => 'required|exists:games,id',
            'player_id' => 'required|exists:players,id',
            'minute' => 'required|date_format:H:i',
            'comments' => 'string|nullable',
        ];
    }
}
