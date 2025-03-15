<?php

namespace App\Services;

use App\Models\LeagueField;
use App\Models\Location;
use App\Models\Tournament;
use App\Models\TournamentConfiguration;
use App\Models\TournamentField;
use App\Models\TournamentPhase;
use App\Models\TournamentTiebreaker;
use Illuminate\Support\Carbon;
use RuntimeException;

class ScheduleGeneratorService
{
    public const DAYS = [
        'sunday' => 'Domingo',
        'monday' => 'Lunes',
        'tuesday' => 'Martes',
        'wednesday' => 'Miércoles',
        'thursday' => 'Jueves',
        'friday' => 'Viernes',
        'saturday' => 'Sábado'
    ];
    private Tournament $tournament;

    public function setTournament(Tournament $tournament): self
    {
        $this->tournament = $tournament;
        return $this;
    }

    public function generateFor(Tournament $tournament): void
    {
        $schedule = $this->makeSchedule($tournament->teams);
    }

    public function makeSchedule()
    {
        $config = $this->tournament->configuration;
        $teams = $this->tournament->teams()->pluck('teams.id')->toArray();
        $fields = TournamentField::where('tournament_id', $this->tournament->id)->get();
        if (count($teams) < 2) {
            throw new RuntimeException("No hay suficientes equipos para generar encuentros.");
        }
        $scheduleDate = Carbon::parse($this->tournament->start_date);
        $matches = [];
        for ($i = 0, $iMax = count($teams); $i < $iMax; $i++) {
            for ($j = $i + 1, $jMax = count($teams); $j < $jMax; $j++) {
                $field = $fields->random();
                $availability = $field->availability;
                $day = array_key_first($availability);
                $matchTime = Carbon::parse($scheduleDate)->setTime($availability[$day]['start']['hours'], $availability[$day]['start']['minutes']);

                $matches[] = [
                    'tournament_id' => $this->tournament->id,
                    'team_home_id' => $teams[$i],
                    'team_away_id' => $teams[$j],
                    'field_id' => $field->field_id,
                    'match_date' => $matchTime,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $scheduleDate->addMinutes($config->game_time + $config->time_between_games);
            }
        }
        return $matches;
    }

    public function saveConfiguration($data): self
    {
        $this->saveTournamentConfiguration(array_merge($data['general'], [
            'round_trip' => $data['regular_phase']['round_trip'],
            'group_stage' => $data['regular_phase']['group_stage'] ?? false,
            'elimination_round_trip' => $data['elimination_phase']['round_trip']
        ]));
        $this->saveTiebreakers($data['regular_phase']['tiebreakers']);
        $this->saveEliminationPhase($data['elimination_phase']);
        $this->saveFieldsPhase($data['fields_phase']);
        return $this;
    }

    private function saveTournamentConfiguration($generalData): void
    {
        $tournament = Tournament::where('id', $this->tournament->id)
            ->where('league_id', auth()->user()->league->id)
            ->firstOrFail();
        $startDate = Carbon::parse($generalData['start_date']);

        if ($startDate->isPast()) {
            throw new RuntimeException('La fecha de inicio no puede ser en el pasado.');
        }

        $locations = collect($generalData['locations'])->pluck('id');

        $availableLocations = Location::whereIn('id', $locations)->get();
        if ($availableLocations->isEmpty()) {
            throw new RuntimeException('No hay locaciones disponibles para este torneo.');
        }

        $tournament->configuration()->save(
            TournamentConfiguration::updateOrCreate(
                ['tournament_id' => $this->tournament->id],
                [
                    'tournament_format_id' => $generalData['tournament_format_id'],
                    'football_type_id' => $generalData['football_type_id'],
                    'game_time' => $generalData['game_time'],
                    'time_between_games' => $generalData['time_between_games'],
                    'round_trip' => $generalData['round_trip'],
                    'group_stage' => $generalData['group_stage'] ?? false,
                    'elimination_round_trip' => $generalData['round_trip']
                ]
            )
        );
    }

    private function saveTiebreakers($data): void
    {
        $tournamentConfigurationId = $this->tournament->configuration->id;
        $tiebreakers = collect($data)->map(function ($tiebreaker) use ($tournamentConfigurationId) {
            return [
                'tournament_configuration_id' => $tournamentConfigurationId,
                'rule' => $tiebreaker['rule'],
                'priority' => $tiebreaker['priority'],
                'is_active' => $tiebreaker['is_active'],
            ];
        })->toArray();
        foreach ($tiebreakers as $tiebreaker) {
            $this->tournament->configuration->tiebreakers()->save(
                TournamentTiebreaker::updateOrCreate(
                    ['tournament_configuration_id' => $this->tournament->configuration->id, 'rule' => $tiebreaker['rule']],
                    $tiebreaker
                )
            );
        }
    }

    private function saveEliminationPhase($data): void
    {
        unset($data['teams_to_next_round'], $data['round_trip']);
        foreach ($data['phases'] as $eliminationPhase) {
            $this->tournament->phases()->save(
                TournamentPhase::updateOrCreate(
                    ['tournament_id' => $this->tournament->id, 'name' => $eliminationPhase['name']],
                    $eliminationPhase
                )
            );
        }
    }

    private function saveFieldsPhase($data): void
    {
        $fields = array_map(static function ($field) {
            unset($field['availability']['isCompleted']);
            $field['availability'] = array_filter($field['availability'], static fn($day) => $day['enabled']);
            return $field;
        }, $data);

        foreach ($fields as $field) {
            $leagueField = LeagueField::where('field_id', $field['field_id'])->first();
            if (!$leagueField) {
                throw new RuntimeException("El campo {$field['field_name']} no tiene disponibilidad configurada en la liga.");
            }
            $leagueAvailability = $leagueField->availability;
            foreach ($field['availability'] as $day => $schedule) {
                if ($day === 'isCompleted') {
                    continue;
                }

                if (!isset($leagueAvailability[$day]) || !$leagueAvailability[$day]['enabled']) {
                    throw new RuntimeException("El campo {$field['field_name']} no tiene disponibilidad configurada en la liga para el día $day.");
                }

                $requestedStart = (int)$schedule['start']['hours'] * 60 + (int)$schedule['start']['minutes'];
                $requestedEnd = (int)$schedule['end']['hours'] * 60 + (int)$schedule['end']['minutes'];

                $globalStart = (int)$leagueAvailability[$day]['start']['hours'] * 60 + (int)$leagueAvailability[$day]['start']['minutes'];
                $globalEnd = (int)$leagueAvailability[$day]['end']['hours'] * 60 + (int)$leagueAvailability[$day]['end']['minutes'];

                if ($requestedStart < $globalStart || $requestedEnd > $globalEnd) {
                    throw new RuntimeException(sprintf(
                        'El horario %s:%s hasta %s:%s solicitado para el campo %s no está dentro del horario de disponibilidad %s:%s hasta %s:%s de la liga para el día %s. ',
                        $schedule['start']['hours'],
                        $schedule['start']['minutes'],
                        $schedule['end']['hours'],
                        $schedule['end']['minutes'],
                        $field['field_name'],
                        $leagueAvailability[$day]['start']['hours'],
                        $leagueAvailability[$day]['start']['minutes'],
                        $leagueAvailability[$day]['end']['hours'],
                        $leagueAvailability[$day]['end']['minutes'],
                        self::DAYS[$day]
                    ));
                }

                $conflict = TournamentField::where('field_id', $field['field_id'])
                    ->whereJsonContains('availability', [$day])
                    ->get();
                foreach ($conflict as $existingField) {
                    $existingAvailability = $existingField->availability;

                    if (isset($existingAvailability[$day])) {
                        $existingStart = (int)$existingAvailability[$day]['start']['hours'] * 60 + (int)$existingAvailability[$day]['start']['minutes'];
                        $existingEnd = (int)$existingAvailability[$day]['end']['hours'] * 60 + (int)$existingAvailability[$day]['end']['minutes'];

                        if (!($requestedEnd <= $existingStart || $requestedStart >= $existingEnd)) {
                            throw new RuntimeException(sprintf(
                                'El horario %s:%s hasta %s:%s solicitado para el campo %s se cruza con el horario %s:%s hasta %s:%s de otro torneo en el día %s. ',
                                $schedule['start']['hours'],
                                $schedule['start']['minutes'],
                                $schedule['end']['hours'],
                                $schedule['end']['minutes'],
                                $field['field_name'],
                                $existingAvailability[$day]['start']['hours'],
                                $existingAvailability[$day]['start']['minutes'],
                                $existingAvailability[$day]['end']['hours'],
                                $existingAvailability[$day]['end']['minutes'],
                                self::DAYS[$day]
                            ));
                        }
                    }
                }
                TournamentField::updateOrCreate(['field_id' => $field['field_id'], 'tournament_id' => $this->tournament->id],
                    [
                        'availability' => $field['availability']
                    ]);
            }

        }
    }
}
