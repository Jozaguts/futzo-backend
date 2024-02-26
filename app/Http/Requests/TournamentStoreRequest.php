<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'name' => 'required',
            'location' => 'string|nullable',
            'start_date' =>'string|nullable',
            'end_date' => 'string|nullable',
            'prize' => 'string|nullable',
            'winner' => 'string|nullable',
            'description' => 'string|nullable',
            'category_id' => 'exists:categories,id|nullable',
            'status' => 'string|nullable',
        ];
    }
}
