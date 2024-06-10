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
        return[
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tournaments')->where(function ($query) {
                    return $query->where('category_id', $this->category_id);
                }),],
            'tournament_format_id' => 'exists:tournament_formats,id',
            'location' => 'string|nullable',
            'start_date' =>'string|nullable',
            'end_date' => 'string|nullable',
            'prize' => 'string|nullable',
            'winner' => 'string|nullable',
            'description' => 'string|nullable',
            'category_id' => 'exists:categories,id|nullable',
            'status' => 'string|nullable',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }
    public function messages()
    {
        return [
            'name.unique' => 'El nombre del torneo ya ha sido tomado.',
        ];
    }
}
