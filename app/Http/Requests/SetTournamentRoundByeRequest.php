<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetTournamentRoundByeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'bye_team_id' => 'required|integer|exists:teams,id',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
