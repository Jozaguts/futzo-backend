<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTournamentRoundRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'matches' => 'required|array',
            'matches.*.id' => 'required|integer',
            'matches.*.home' => 'required|array',
            'matches.*.home.id' => 'required|integer',
            'matches.*.home.goals' => 'required|integer',
            'matches.*.away' => 'required|array',
            'matches.*.away.id' => 'required|integer',
            'matches.*.away.goals' => 'required|integer',
            'matches.*.penalties' => 'array|nullable',
            'matches.*.penalties.decided' => 'boolean',
            'matches.*.penalties.home_goals' => 'nullable|integer|min:0',
            'matches.*.penalties.away_goals' => 'nullable|integer|min:0',
            'matches.*.penalties.winner_team_id' => 'nullable|integer',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
