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
        return[
            'name' => 'string|max:255',
            'tournament_format_id' => 'exists:tournament_formats,id',
            'start_date' =>'string|nullable',
            'end_date' => 'string|nullable',
            'prize' => 'string|nullable',
            'winner' => 'string|nullable',
            'description' => 'string|nullable',
            'category_id' => 'exists:categories,id|nullable',
            'status' => 'string|nullable',
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'location' => 'json',
        ];
    }
    public function messages(): array
    {
        return [
            'name.unique' => 'El nombre del torneo ya ha sido tomado.',
        ];
    }
}
