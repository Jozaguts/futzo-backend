<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportTeamsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:xls,xlsx|max:8192',
            'tournament_id' => 'required|exists:tournaments,id',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
