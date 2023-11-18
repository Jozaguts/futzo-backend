<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RefereeUpdateRequest extends FormRequest
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
            'name' => 'string|required',
            'paternal_name' => 'string|required',
            'password' => 'string',
            'email' => 'string',
            'maternal_name' => 'string|required',
            'birthdate' => 'string|date',
            'phone' => 'digits:10|integer|min:10',
        ];
    }
}
