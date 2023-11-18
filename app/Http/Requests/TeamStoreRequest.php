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
            'group' => 'string|max:1|nullable',
            'category_id' => 'required|exists:categories,id',
            'won' => 'integer|nullable',
            'draw' => 'integer|nullable',
            'lost' => 'integer|nullable',
            'goals_against' => 'integer|nullable',
            'goals_for' => 'integer|nullable',
            'goals_difference' => 'integer|nullable',
            'points' => 'integer|nullable',
        ];
    }
}
