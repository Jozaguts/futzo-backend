<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TournamentFieldRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tournament_id' => ['required', 'exists:tournaments'],
            'field_id' => ['required', 'exists:fields'],
            'availability' => ['nullable'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
