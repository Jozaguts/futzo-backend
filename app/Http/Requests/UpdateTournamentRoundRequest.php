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
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
