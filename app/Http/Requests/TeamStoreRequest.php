<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Validation\Rule;

/**
 * @property mixed category_id
 */
class TeamStoreRequest extends FormRequest
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
            'name' => [
                'required',
                Rule::unique('teams')->where(function($q) {
                    return $q->where('category_id', $this->category_id);
                }),
            ],
            'address' => 'string|nullable',
            'location' => 'string|nullable',
            'city' => 'string|nullable',
            'email' => 'string|nullable',
            'phone' => 'string|required',
            'image' => [
                ...$this->isPrecognitive() ? [] : ['nullable'],
                'image',
                'mimes:jpg,png',
            ],
            'category_id' => 'required|exists:categories,id',
            'tournament_id' => 'required|exists:tournaments,id',
            'locale.short' => 'string|nullable',
            'locale.jersey' => 'string|nullable',
            'away.short' => 'string|nullable',
            'away.jersey' => 'string|nullable',

//            'won' => 'integer|nullable',
//            'draw' => 'integer|nullable',
//            'lost' => 'integer|nullable',
//            'goals_against' => 'integer|nullable',
//            'goals_for' => 'integer|nullable',
//            'goals_difference' => 'integer|nullable',
//            'points' => 'integer|nullable',
        ];
    }
}
