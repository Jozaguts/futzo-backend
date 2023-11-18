<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TeamUpdateRequest extends FormRequest
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
            'name' => 'string',
            'group' => 'string|max:1',
            'category_id' => 'required|exists:categories,id',
            'won' => 'integer',
            'draw' => 'integer',
            'lost' => 'integer',
            'goals_against' => 'integer',
            'goals_for' => 'integer',
            'goals_difference' => 'integer',
            'points' => 'integer',
        ];
    }
}
