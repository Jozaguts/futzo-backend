<?php

namespace App\Http\Requests;

use App\Models\TournamentConfiguration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CreateTournamentScheduleRequest extends FormRequest
{
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
            'elimination_phase.phases.*.name' => 'required|string',
            'elimination_phase.phases.*.is_active' => 'required|boolean',
            'elimination_phase.phases.*.is_completed' => 'required|boolean',
            'elimination_phase.phases.*.tournament_id' => 'required|integer',

            // Validación de "fields_phase"
            'fields_phase' => 'required|array',
            'fields_phase.*.field_id' => 'required|integer',
            'fields_phase.*.step' => 'required|integer',
            'fields_phase.*.field_name' => 'required|string',
            'fields_phase.*.location_name' => 'required|string',
            'fields_phase.*.location_id' => 'required|integer',
            'fields_phase.*.availability' => 'required|array',
            'fields_phase.*.availability.isCompleted' => 'required|boolean',

            // Validación de cada día dentro de "availability"
            'fields_phase.*.availability.monday' => 'required|array',
            'fields_phase.*.availability.monday.start' => 'required|array',
            'fields_phase.*.availability.monday.start.hours' => 'required|string',
            'fields_phase.*.availability.monday.start.minutes' => 'required|string',
            'fields_phase.*.availability.monday.end' => 'required|array',
            'fields_phase.*.availability.monday.end.hours' => 'required|string',
            'fields_phase.*.availability.monday.end.minutes' => 'required|string',
            'fields_phase.*.availability.monday.enabled' => 'required|boolean',

            'fields_phase.*.availability.tuesday' => 'required|array',
            'fields_phase.*.availability.tuesday.start' => 'required|array',
            'fields_phase.*.availability.tuesday.start.hours' => 'required|string',
            'fields_phase.*.availability.tuesday.start.minutes' => 'required|string',
            'fields_phase.*.availability.tuesday.end' => 'required|array',
            'fields_phase.*.availability.tuesday.end.hours' => 'required|string',
            'fields_phase.*.availability.tuesday.end.minutes' => 'required|string',
            'fields_phase.*.availability.tuesday.enabled' => 'required|boolean',

            'fields_phase.*.availability.wednesday' => 'required|array',
            'fields_phase.*.availability.wednesday.start' => 'required|array',
            'fields_phase.*.availability.wednesday.start.hours' => 'required|string',
            'fields_phase.*.availability.wednesday.start.minutes' => 'required|string',
            'fields_phase.*.availability.wednesday.end' => 'required|array',
            'fields_phase.*.availability.wednesday.end.hours' => 'required|string',
            'fields_phase.*.availability.wednesday.end.minutes' => 'required|string',
            'fields_phase.*.availability.wednesday.enabled' => 'required|boolean',

            'fields_phase.*.availability.thursday' => 'required|array',
            'fields_phase.*.availability.thursday.start' => 'required|array',
            'fields_phase.*.availability.thursday.start.hours' => 'required|string',
            'fields_phase.*.availability.thursday.start.minutes' => 'required|string',
            'fields_phase.*.availability.thursday.end' => 'required|array',
            'fields_phase.*.availability.thursday.end.hours' => 'required|string',
            'fields_phase.*.availability.thursday.end.minutes' => 'required|string',
            'fields_phase.*.availability.thursday.enabled' => 'required|boolean',

            'fields_phase.*.availability.friday' => 'required|array',
            'fields_phase.*.availability.friday.start' => 'required|array',
            'fields_phase.*.availability.friday.start.hours' => 'required|string',
            'fields_phase.*.availability.friday.start.minutes' => 'required|string',
            'fields_phase.*.availability.friday.end' => 'required|array',
            'fields_phase.*.availability.friday.end.hours' => 'required|string',
            'fields_phase.*.availability.friday.end.minutes' => 'required|string',
            'fields_phase.*.availability.friday.enabled' => 'required|boolean',

            'fields_phase.*.availability.saturday' => 'required|array',
            'fields_phase.*.availability.saturday.start' => 'required|array',
            'fields_phase.*.availability.saturday.start.hours' => 'required|string',
            'fields_phase.*.availability.saturday.start.minutes' => 'required|string',
            'fields_phase.*.availability.saturday.end' => 'required|array',
            'fields_phase.*.availability.saturday.end.hours' => 'required|string',
            'fields_phase.*.availability.saturday.end.minutes' => 'required|string',
            'fields_phase.*.availability.saturday.enabled' => 'required|boolean',

            'fields_phase.*.availability.sunday' => 'required|array',
            'fields_phase.*.availability.sunday.start' => 'required|array',
            'fields_phase.*.availability.sunday.start.hours' => 'required|string',
            'fields_phase.*.availability.sunday.start.minutes' => 'required|string',
            'fields_phase.*.availability.sunday.end' => 'required|array',
            'fields_phase.*.availability.sunday.end.hours' => 'required|string',
            'fields_phase.*.availability.sunday.end.minutes' => 'required|string',
            'fields_phase.*.availability.sunday.enabled' => 'required|boolean',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function tournamentConfigurationData(): array
    {
        $validated = $this->validated();

        return [
            'tournament_format_id' => $validated['general']['tournament_format_id'],
            'football_type_id' => $validated['general']['football_type_id'],
            'game_time' => $validated['general']['game_time'],
            'time_between_games' => $validated['general']['time_between_games'],
            'round_trip' => $validated['regular_phase']['round_trip'],
            'group_stage' => $validated['regular_phase']['group_stage'] ?? false,
            'elimination_round_trip' => $validated['elimination_phase']['round_trip']
        ];
    }
}
