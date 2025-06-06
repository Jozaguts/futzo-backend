<?php

namespace App\Http\Requests;

use App\Models\TournamentConfiguration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CreateTournamentScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tournamentId = $this->input('general.tournament_id');

        $config = DB::table('tournament_configurations')
            ->where('tournament_id', $tournamentId)
            ->first(['min_teams', 'max_teams']);

        $minTeams = $config->min_teams ?? 8;
        $maxTeams = $config->max_teams ?? 40;
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
            'general.start_date' => 'required|date|after:today',
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
                Rule::exists('location_tournament', 'location_id')->where(function ($query) {
                    return $query->where('tournament_id', request('general.tournament_id'));
                }),
                Rule::exists('league_location', 'location_id')->where(function ($query) {
                    return $query->whereIn('league_id', function ($subQuery) {
                        $subQuery->select('league_id')->from('tournaments')->where('id', request('general.tournament_id'));
                    });
                }),
            ],
            'general.locations.*.name' => 'required|string',

            // Validación de "regular_phase"
            'regular_phase' => 'required|array',
            'regular_phase.round_trip' => 'required|boolean',
            'regular_phase.tiebreakers' => 'required|array',
            'regular_phase.tiebreakers.*.id' => 'required|integer',
            'regular_phase.tiebreakers.*.rule' => 'required|string',
            'regular_phase.tiebreakers.*.priority' => 'required|integer',
            'regular_phase.tiebreakers.*.is_active' => 'required|boolean',
            'regular_phase.tiebreakers.*.tournament_configuration_id' => 'required|integer',

            // Validación de "elimination_phase"
            'elimination_phase' => 'required|array',
            'elimination_phase.teams_to_next_round' => 'required|integer',
            'elimination_phase.round_trip' => 'required|boolean',
            'elimination_phase.phases' => 'required|array',
            'elimination_phase.phases.*.id' => 'required|integer',
            'elimination_phase.phases.*.name' => 'required|string|exists:phases,name',
            'elimination_phase.phases.*.is_active' => 'required|boolean',
            'elimination_phase.phases.*.is_completed' => 'required|boolean',
            'elimination_phase.phases.*.tournament_id' => 'required|integer',


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

        ];
    }
}
