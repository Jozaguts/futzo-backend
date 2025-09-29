<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Enums\TournamentFormatId;

class CreateTournamentScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tournamentId = $this->input('general.tournament_id');

        $formatId = (int) $this->input('general.tournament_format_id');

        $config = DB::table('tournament_configurations')
            ->where('tournament_id', $tournamentId)
            ->first(['min_teams', 'max_teams']);

        $minTeams = $config->min_teams ?? 8;
        $maxTeams = $config->max_teams ?? 36;

        if ($formatId === TournamentFormatId::GroupAndElimination->value) {
            $maxTeams = min($maxTeams, 36);
        }
        return [
            // Validación de "general"
            'general' => 'required|array',
            'general.tournament_id' => [
                'required',
                'integer',
                'exists:tournaments,id',
                Rule::exists('tournament_configurations', 'tournament_id'),
            ],
            'general.tournament_format_id' => 'required|integer|exists:tournament_formats,id',
            'general.football_type_id' => 'required|integer|exists:football_types,id',
            'general.start_date' => 'required|date',
            'general.game_time' => ['required', 'integer', 'min:1'],
            'general.time_between_games' => ['required', 'integer', 'min:0'],
            'general.total_teams' => [
                'required',
                'integer',
                'min:' . $minTeams,
                'max:' . $maxTeams,
            ],
            'general.locations' => 'required|array',
            'general.locations.*.id' => [
                'required',
                'integer',
                'exists:locations,id',
                Rule::exists('location_tournament', 'location_id')->where(function ($query) use ($tournamentId) {
                    return $query->where('tournament_id', $tournamentId);
                }),
                Rule::exists('league_location', 'location_id')->where(function ($query) use ($tournamentId) {
                    return $query->whereIn('league_id', function ($subQuery) use ($tournamentId) {
                        $subQuery->select('league_id')->from('tournaments')->where('id', $tournamentId);
                    });
                }),
            ],
            'general.locations.*.name' => 'required|string',

            // Validación de "rules_phase"
            'rules_phase' => 'required|array',
            'rules_phase.round_trip' => 'required|boolean',
            'rules_phase.tiebreakers' => 'required|array',
            'rules_phase.tiebreakers.*.id' => 'required|integer',
            'rules_phase.tiebreakers.*.rule' => 'required|string',
            'rules_phase.tiebreakers.*.priority' => 'required|integer',
            'rules_phase.tiebreakers.*.is_active' => 'required|boolean',
            'rules_phase.tiebreakers.*.tournament_configuration_id' => 'required|integer',

            // Validación de "elimination_phase"
            'elimination_phase' => 'required|array',
            'elimination_phase.teams_to_next_round' => 'required|integer',
            'elimination_phase.elimination_round_trip' => 'required|boolean',
            'elimination_phase.phases' => 'required|array',
            'elimination_phase.phases.*.id' => 'required|integer',
            'elimination_phase.phases.*.name' => 'required|string|exists:phases,name',
            'elimination_phase.phases.*.is_active' => 'required|boolean',
            'elimination_phase.phases.*.is_completed' => 'required|boolean',
            'elimination_phase.phases.*.tournament_id' => 'required|integer',
            // Reglas por fase (opcional)
            'elimination_phase.phases.*.rules' => 'nullable|array',
            'elimination_phase.phases.*.rules.round_trip' => 'sometimes|boolean',
            'elimination_phase.phases.*.rules.away_goals' => 'sometimes|boolean',
            'elimination_phase.phases.*.rules.extra_time' => 'sometimes|boolean',
            'elimination_phase.phases.*.rules.penalties' => 'sometimes|boolean',
            'elimination_phase.phases.*.rules.advance_if_tie' => 'sometimes|in:better_seed,none',


            'fields_phase' => 'required|array',
            'fields_phase.*.field_id' => 'required|integer',
            'fields_phase.*.step' => 'required|integer',
            'fields_phase.*.field_name' => 'required|string',
            'fields_phase.*.location_name' => 'required|string',
            'fields_phase.*.location_id' => 'required|integer',
            'fields_phase.*.disabled' => 'required|boolean',

            'fields_phase.*.availability' => 'required|array',
            'fields_phase.*.availability.isCompleted' => 'required|boolean',

            'fields_phase.*.availability.monday' => 'nullable|array',
            'fields_phase.*.availability.monday.enabled' => 'sometimes|required|boolean',
            'fields_phase.*.availability.monday.available_range' => 'sometimes|required|string',
            'fields_phase.*.availability.monday.intervals' => 'sometimes|required|array',
            'fields_phase.*.availability.monday.label' => 'sometimes|required|string',

            'fields_phase.*.availability.tuesday' => 'nullable|array',
            'fields_phase.*.availability.tuesday.enabled' => 'sometimes|required|boolean',
            'fields_phase.*.availability.tuesday.available_range' => 'sometimes|required|string',
            'fields_phase.*.availability.tuesday.intervals' => 'sometimes|required|array',
            'fields_phase.*.availability.tuesday.label' => 'sometimes|required|string',

            'fields_phase.*.availability.wednesday' => 'nullable|array',
            'fields_phase.*.availability.wednesday.enabled' => 'sometimes|required|boolean',
            'fields_phase.*.availability.wednesday.available_range' => 'sometimes|required|string',
            'fields_phase.*.availability.wednesday.intervals' => 'sometimes|required|array',
            'fields_phase.*.availability.wednesday.label' => 'sometimes|required|string',

            'fields_phase.*.availability.thursday' => 'nullable|array',
            'fields_phase.*.availability.thursday.enabled' => 'sometimes|required|boolean',
            'fields_phase.*.availability.thursday.available_range' => 'sometimes|required|string',
            'fields_phase.*.availability.thursday.intervals' => 'sometimes|required|array',
            'fields_phase.*.availability.thursday.label' => 'sometimes|required|string',

            'fields_phase.*.availability.friday' => 'nullable|array',
            'fields_phase.*.availability.friday.enabled' => 'sometimes|required|boolean',
            'fields_phase.*.availability.friday.available_range' => 'sometimes|required|string',
            'fields_phase.*.availability.friday.intervals' => 'sometimes|required|array',
            'fields_phase.*.availability.friday.label' => 'sometimes|required|string',

            'fields_phase.*.availability.saturday' => 'nullable|array',
            'fields_phase.*.availability.saturday.enabled' => 'sometimes|required|boolean',
            'fields_phase.*.availability.saturday.available_range' => 'sometimes|required|string',
            'fields_phase.*.availability.saturday.intervals' => 'sometimes|required|array',
            'fields_phase.*.availability.saturday.label' => 'sometimes|required|string',

            'fields_phase.*.availability.sunday' => 'nullable|array',
            'fields_phase.*.availability.sunday.enabled' => 'sometimes|required|boolean',
            'fields_phase.*.availability.sunday.available_range' => 'sometimes|required|string',
            'fields_phase.*.availability.sunday.intervals' => 'sometimes|required|array',
            'fields_phase.*.availability.sunday.label' => 'sometimes|required|string',

            // Configuración de fase de grupos (opcional, solo si group_stage=1)
            'group_phase' => 'nullable|array',
            'group_phase.option_id' => 'sometimes|nullable|string',
            'group_phase.selected_option' => 'sometimes',
            'group_phase.option' => 'sometimes',
            'group_phase.teams_per_group' => 'nullable|integer|min:3|max:6',
            'group_phase.advance_top_n' => 'nullable|integer|min:1',
            'group_phase.include_best_thirds' => 'sometimes|boolean',
            'group_phase.best_thirds_count' => 'nullable|integer|min:0',
            'group_phase.group_sizes' => 'nullable|array',
            'group_phase.group_sizes.*' => 'integer|min:3|max:6',

        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $groupPhase = $this->input('group_phase');

            if (is_array($groupPhase)) {
                $optionId = $groupPhase['option_id'] ?? null;

                if ($optionId === null || $optionId === '') {
                    $selectedOption = $groupPhase['selected_option'] ?? null;
                    if (is_array($selectedOption)) {
                        $optionId = $selectedOption['id'] ?? null;
                    } elseif (is_string($selectedOption) || is_int($selectedOption)) {
                        $optionId = $selectedOption;
                    }
                }

                if ($optionId === null || $optionId === '') {
                    $option = $groupPhase['option'] ?? null;
                    if (is_string($option) || is_int($option)) {
                        $optionId = $option;
                    }
                }

                if ($optionId === null || $optionId === '') {
                    if (!array_key_exists('teams_per_group', $groupPhase)
                        || $groupPhase['teams_per_group'] === null
                        || $groupPhase['teams_per_group'] === ''
                    ) {
                        $validator->errors()->add(
                            'group_phase.teams_per_group',
                            'El campo group phase.teams per group es obligatorio cuando no se selecciona una opción predefinida.'
                        );
                    }

                    if (!array_key_exists('advance_top_n', $groupPhase)
                        || $groupPhase['advance_top_n'] === null
                        || $groupPhase['advance_top_n'] === ''
                    ) {
                        $validator->errors()->add(
                            'group_phase.advance_top_n',
                            'El campo group phase.advance top n es obligatorio cuando no se selecciona una opción predefinida.'
                        );
                    }
                }
            }

            $groupSizes = is_array($groupPhase)
                ? ($groupPhase['group_sizes'] ?? null)
                : $this->input('group_phase.group_sizes');

            $totalTeams = (int) $this->input('general.total_teams');
            $hasGroupSizes = is_array($groupSizes) && count($groupSizes) > 0;

            if ($hasGroupSizes) {
                $configuredSizes = array_values(array_map('intval', $groupSizes));
                $configuredTotal = array_sum($configuredSizes);

                if ($configuredTotal !== $totalTeams) {
                    $validator->errors()->add(
                        'group_phase.group_sizes',
                        'La suma de los tamaños de grupo debe coincidir con el total de equipos (' . $totalTeams . ').'
                    );
                }

                if (count($configuredSizes) === 1 && $configuredSizes[0] === $totalTeams) {
                    $validator->errors()->add(
                        'group_phase.group_sizes',
                        'Debe configurar al menos dos grupos distintos para la fase de grupos.'
                    );
                }

            }
            if (!$hasGroupSizes) {
                $teamsPerGroup = $this->input('group_phase.teams_per_group');
                if ($teamsPerGroup !== null && $teamsPerGroup !== '') {
                    $teamsPerGroup = (int) $teamsPerGroup;
                    if ($teamsPerGroup === $totalTeams) {
                        $validator->errors()->add(
                            'group_phase.teams_per_group',
                            'Debe configurar al menos dos grupos distintos para la fase de grupos.'
                        );
                    }
                }
            }

            $formatId = (int) $this->input('general.tournament_format_id');
            $totalTeams = (int) $this->input('general.total_teams');

            if (
                $formatId === TournamentFormatId::GroupAndElimination->value
                && $totalTeams % 2 === 1
                && $totalTeams > 36
            ) {
                $validator->errors()->add(
                    'general.total_teams',
                    'Los torneos con fase de grupos admiten un máximo de 36 equipos cuando el total es impar.'
                );
            }
        });
    }
    public function messages(): array
    {
        return [
            'general.total_teams.min' => 'El número mínimo de equipos es :min.',
            'general.total_teams.max' => 'El número máximo de equipos es :max.',
            'general.locations.*.id.exists' => 'La ubicación seleccionada no está asociada al torneo o a la liga correspondiente.',
        ];
    }
}
