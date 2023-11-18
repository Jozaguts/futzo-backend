<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RefereeStoreRequest extends FormRequest
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
            'maternal_name' => 'string|required',
            'email' => 'string|required',
            'birthdate' => 'string|date',
            'phone' => 'digits:10|integer|min:10',
        ];
    }
}
