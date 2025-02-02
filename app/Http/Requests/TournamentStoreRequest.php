<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TournamentStoreRequest extends FormRequest
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
            'basic.name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tournaments', 'name')->where(function ($query) {
                    return $query->where('category_id', $this->input('basic.category_id'));
                })
            ],
            'basic.football_type_id' => 'required|exists:football_types,id',
            'basic.image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'basic.tournament_format_id' => 'exists:tournament_formats,id',
            'basic.category_id' => 'exists:categories,id|nullable',
            'basic.start_date' => 'date|nullable',
            'basic.end_date' => 'date|nullable',
            'basic.minMax' => 'json|required',
            'details.prize' => 'string|nullable',
            'details.winner' => 'string|nullable',
            'details.description' => 'string|nullable',
            'details.status' => 'string|nullable',
            'details.location' => 'json',
        ];
    }

    public function messages()
    {
        return [
            'basic.name.unique' => 'El nombre del torneo ya ha sido tomado.',
        ];
    }
}
