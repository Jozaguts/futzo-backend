<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetTournamentRoundScheduleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'matches' => 'required|array|min:1',
            'matches.*.home_team_id' => 'required|integer|exists:teams,id',
            'matches.*.away_team_id' => 'required|integer|exists:teams,id',
            'bye_team_id' => 'nullable|integer|exists:teams,id',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
