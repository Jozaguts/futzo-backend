<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DefaultLineupRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'team_id' => ['required', 'exists:teams'],
            'formation' => ['required'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
