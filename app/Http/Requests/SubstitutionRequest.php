<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubstitutionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'home.*.player_in_id' => ['exists:players,id'],
            'home.*.player_out_id' => ['exists:players,id'],
            'away.*.player_in_id' => ['exists:players,id'],
            'away.*.player_out_id' => ['exists:players,id'],
            'home.*.minute' => ['integer'],
            'away.*.minute' => ['integer'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
