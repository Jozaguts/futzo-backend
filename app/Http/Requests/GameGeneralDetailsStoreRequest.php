<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GameGeneralDetailsStoreRequest extends FormRequest
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
            'game_id'=> 'required|exists:games,id',
            'local_color'=> 'required|string',
            'away_color'=> 'required|string',
            'local_captain_id'=> 'required|exists:users,id',
            'away_captain_id'=> 'required|exists:users,id',
            'referee_id'=> 'exists:users,id',
            'first_assistance_referee_id'=> 'exists:users,id',
            'second_referee_id'=> 'exists:users,id',
            'third_referee_id'=> 'exists:users,id',
        ];
    }
}
