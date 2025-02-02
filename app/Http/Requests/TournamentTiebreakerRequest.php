<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TournamentTiebreakerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'rule' => ['required'],
            'priority' => ['required', 'integer'],
            'is_active' => ['boolean'],
            'tournament_configuration_id' => ['required', 'exists:tournament_configurations'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
