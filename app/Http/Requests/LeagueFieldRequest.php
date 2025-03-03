<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeagueFieldRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'league_id' => ['required', 'exists:leagues'],
            'field_id' => ['required', 'exists:fields'],
            'availability' => ['nullable'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
