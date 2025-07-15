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
                'team.address' => 'nullable|json',
                'team.image' => 'nullable|image|mimes:jpg,png,svg',
                'team.colors' => 'nullable|json',
                'team.colors.home' => 'nullable|json',
                'team.colors.home.primary' => 'nullable|string',
                'team.colors.home.secondary' => 'nullable|string',
                'team.colors.away' => 'nullable|json',
                'team.colors.away.primary' => 'nullable|string',
                'team.colors.away.secondary' => 'nullable|string',
                'team.email' => 'nullable|email',
                'team.phone' => 'nullable|string',
                'team.category_id' => 'required|exists:categories,id',
                'team.tournament_id' => 'required|exists:tournaments,id',

                'president.name' => 'nullable|string',
                'president.phone' => 'nullable|string|unique:users,phone',
                'president.email' => 'nullable|email|string',
                'president.image' => 'nullable|image|mimes:jpg,png,svg',

                'coach.name' => 'nullable|string',
                'coach.phone' => 'nullable|string|unique:users,phone',
                'coach.email' => 'nullable|email|string',
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

    public function messages(): array
    {
        return [
            'team.name.required' => 'El nombre del equipo es requerido',
            'team.address.string' => 'La dirección del equipo debe ser una cadena de texto',
            'team.image.image' => 'La imagen del equipo debe ser un archivo de imagen',
            'team.image.mimes' => 'La imagen del equipo debe ser un archivo de imagen con formato jpg, png o svg',
            'team.colors.home.primary.string' => 'El color de la camiseta de local debe ser una cadena de texto',
            'team.colors.home.secondary.string' => 'El color del short de local debe ser una cadena de texto',
            'team.colors.away.primary.string' => 'El color de la camiseta de visitante debe ser una cadena de texto',
            'team.colors.away.secondary.string' => 'El color del short de visitante debe ser una cadena de texto',
            'team.email.email' => 'El correo electrónico del equipo debe ser una dirección de correo válida',
            'team.phone.string' => 'El teléfono del equipo debe ser una cadena de texto',

            'president.name.required' => 'El nombre del presidente es requerido',
            'president.name.string' => 'El nombre del presidente debe ser una cadena de texto',
            'president.phone.required' => 'El teléfono del presidente es requerido',
            'president.phone.string' => 'El teléfono del presidente debe ser una cadena de texto',
            'president.phone.unique' => 'El teléfono del presidente ya está en uso',
            'president.email.required' => 'El correo electrónico del presidente es requerido',
            'president.email.email' => 'El correo electrónico del presidente debe ser una dirección de correo válida',
            'president.email.string' => 'El correo electrónico del presidente debe ser una cadena de texto',
            'president.image.image' => 'La imagen del presidente debe ser un archivo de imagen',
            'president.image.mimes' => 'La imagen del presidente debe ser un archivo de imagen con formato jpg, png o svg',

            'coach.name.required' => 'El nombre del entrenador es requerido',
            'coach.name.string' => 'El nombre del entrenador debe ser una cadena de texto',
            'coach.phone.required' => 'El teléfono del entrenador es requerido',
            'coach.phone.string' => 'El teléfono del entrenador debe ser una cadena de texto',
        ];
    }
}
