<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

/**
 * @property mixed category_id
 */
class TeamStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        return
            [
                'team.name' => 'required',
                'team.address' => 'nullable|string',
                'team.image' => 'nullable|image|mimes:jpg,png,svg',
                'team.colors.home.jersey' => 'nullable|string',
                'team.colors.home.short' => 'nullable|string',
                'team.colors.away.jersey' => 'nullable|string',
                'team.colors.away.short' => 'nullable|string',
                'team.email' => 'nullable|email',
                'team.phone' => 'nullable|string',

                'president.name' => 'required|string',
                'president.phone' => 'required|string',
                'president.email' => 'required|email|string',
                'president.image' => 'nullable|image|mimes:jpg,png,svg',

                'coach.name' => 'required|string',
                'coach.phone' => 'required|string',
                'coach.email' => 'required|email|string',
                'coach.image' => 'nullable|image|mimes:jpg,png,svg',
            ];
    }
    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            if ($this->teamNameCategoryExists()) {
                $validator->errors()->add('team.name', 'El equipo ya existe en esta categoría');
            }
        });

    }
    protected function teamNameCategoryExists(): bool
    {
        return DB::table('category_team')
            ->join('teams', 'category_team.team_id', '=', 'teams.id')
            ->where('teams.name', $this->input('team.name'))
            ->where('category_team.category_id', $this->input('team.category_id'))
            ->exists();
    }
}
