<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeagueStoreRequest extends FormRequest
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
        return[
            'name' => 'string|min:6|required',
            'location' => 'string|nullable',
            'football_type_id' => 'nullable|integer|exists:football_types,id',
            'description' => 'string|nullable',
            'creation_date' =>'string|nullable',
            'logo' => [
                ...$this->isPrecognitive() ? [] : ['nullable'],
                'image',
                'mimes:jpg,png',
            ],
            'banner' => [
                ...$this->isPrecognitive() ? [] : ['nullable'],
                'image',
                'mimes:jpg,png',
            ],
            'status' => 'string|nullable',
        ];
    }
}
