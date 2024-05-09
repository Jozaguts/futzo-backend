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
            'tournament_id' => 'required|exists:tournaments,id',
            'category_id' => 'required|exists:categories,id',
            'president_name' => 'string|required',
            'coach_name' => 'string|required',
            'phone' => 'string|required',
            'email' => 'string|email|nullable',
            'address' => 'string|nullable',
            'image' => [
                ...$this->isPrecognitive() ? [] : ['nullable'],
                'image',
                'mimes:jpg,png',
            ],
            'colors' => [
                'home' => [
                  'jersey' => 'string|nullable',
                  'short' => 'string|nullable'
              ],
                'away' => [
                    'jersey' => 'string|nullable',
                    'short' => 'string|nullable'
                ]
            ],
        ];
    }
}
