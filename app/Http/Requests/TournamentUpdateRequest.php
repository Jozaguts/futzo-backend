<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TournamentUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
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
            'basic.image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'basic.tournament_format_id' => 'exists:tournament_formats,id',
            'basic.category_id' => 'exists:categories,id|nullable',
            'basic.min_max' => 'json|nullable',
            'details.start_date' => 'string|nullable',
            'details.end_date' => 'string|nullable',
            'details.prize' => 'string|nullable',
            'details.winner' => 'string|nullable',
            'details.description' => 'string|nullable',
            'details.status' => 'string|nullable',
            'details.location' => 'json',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'El nombre del torneo ya ha sido tomado.',
        ];
    }
}
