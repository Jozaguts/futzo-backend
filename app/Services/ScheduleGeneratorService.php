<?php

namespace App\Services;

use App\Models\LeagueField;
use App\Models\Location;
use App\Models\MatchSchedule;
use App\Models\Tournament;
use App\Models\TournamentConfiguration;
use App\Models\TournamentField;
use App\Models\TournamentPhase;
use App\Models\TournamentTiebreaker;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

    public function makeSchedule(): array
    {
        $break_time = 15;
        $unexpected_time = 15;
        $config = $this->tournament->configuration;
        $teams = $this->tournament->teams()->pluck('teams.id')->toArray();
        $fields = TournamentField::where('tournament_id', $this->tournament->id)->get();

        if (count($teams) < 2) {
            throw new RuntimeException("No hay suficientes equipos para generar encuentros.");
        }

        $matchDuration = $config->game_time + $break_time + $unexpected_time;
        $leagueAvailabilities = LeagueField::whereIn('field_id', $fields->pluck('field_id'))
            ->get()
            ->keyBy('field_id')
            ->map(fn($f) => $f->availability);

        $fieldLocations = DB::table('fields')
            ->select('id', 'location_id')
            ->pluck('location_id', 'id');

        $fixturesByRound = $this->generateFixtures($teams, $config->round_trip);
        $totalRounds = count($fixturesByRound);
        $weeksToGenerate = $totalRounds + 2;

        $availableSlots = $this->generateAvailableSlots($fields, $this->tournament->start_date, $matchDuration, $leagueAvailabilities, $weeksToGenerate);

        $startOfWeekDay = Carbon::parse($this->tournament->start_date)->dayOfWeek;
        $slotsByWeekAndField = collect($availableSlots)->groupBy(function ($slot) use ($startOfWeekDay) {
            return Carbon::parse($slot['match_time'])->startOfWeek($startOfWeekDay)->toDateString();
        })->map(function ($weekSlots) {
            return $weekSlots->groupBy('field_id');
        });

        Log::info("Resumen de slots agrupados por semana:", $slotsByWeekAndField->map(fn($week) => $week->map->count())->toArray());

        $matches = [];
        $teamRounds = [];
        $weekStart = Carbon::parse($this->tournament->start_date)->startOfWeek($startOfWeekDay);
        $globalFieldIndex = 0;

        foreach ($fixturesByRound as $round => $fixtures) {
            $weekKey = $weekStart->toDateString();
            Log::info("Jornada {$round} intenta asignarse en la semana que inicia en $weekKey");

            if (!isset($slotsByWeekAndField[$weekKey])) {
                throw new RuntimeException("No hay disponibilidad configurada para la semana de la jornada " . ($round + 1));
            }

            $weekFieldSlots = $slotsByWeekAndField[$weekKey];
            $fieldSlotPointers = array_fill_keys(array_keys($weekFieldSlots->toArray()), 0);
            $fieldsAvailable = array_keys($weekFieldSlots->toArray());

            foreach ($fixtures as $match) {
                $assigned = false;
                $attempts = 0;

                while ($attempts < count($fieldsAvailable)) {
                    $fieldId = $fieldsAvailable[$globalFieldIndex % count($fieldsAvailable)];
                    $slots = $weekFieldSlots[$fieldId];
                    $slotPointer = $fieldSlotPointers[$fieldId];

                    if (isset($slots[$slotPointer])) {
                        $slot = $slots[$slotPointer];
                        $fieldSlotPointers[$fieldId]++;
                        $matchTime = $slot['match_time'];
                        $locationId = $fieldLocations[$fieldId] ?? throw new RuntimeException("No se encontró location_id para field_id $fieldId");

                        $matchRound = $round + 1;
                        foreach ([$match[0], $match[1]] as $teamId) {
                            if (isset($teamRounds[$matchRound][$teamId])) {
                                continue 2;
                            }
                        }

                        foreach ([$match[0], $match[1]] as $teamId) {
                            $teamRounds[$matchRound][$teamId] = true;
                        }

                        $matches[] = [
                            'tournament_id' => $this->tournament->id,
                            'home_team_id' => $match[0],
                            'away_team_id' => $match[1],
                            'field_id' => $fieldId,
                            'location_id' => $locationId,
                            'match_date' => $matchTime->toDateString(),
                            'match_time' => $matchTime->format('H:i:s'),
                            'round' => $matchRound,
                            'status' => 'scheduled',
                        ];

                        Log::info("Asignado partido ronda {$matchRound}: {$match[0]} vs {$match[1]} en campo {$fieldId} a las {$matchTime->format('H:i')} ({$matchTime->toDateString()})");
                        $assigned = true;
                        $globalFieldIndex++;
                        break;
                    }
                    $globalFieldIndex++;
                    $attempts++;
                }

                if (!$assigned) {
                    throw new RuntimeException("No hay slots disponibles válidos para el partido entre {$match[0]} y {$match[1]}.");
                }
            }
            $weekStart->addWeek();
        }

        return $matches;
    }

    public function persistScheduleToMatchSchedules(array $matches): void
    {
        foreach ($matches as $match) {
            MatchSchedule::updateOrCreate([
                'tournament_id' => $match['tournament_id'],
                'home_team_id' => $match['home_team_id'],
                'away_team_id' => $match['away_team_id'],
                'match_date' => $match['match_date'],
                'match_time' => $match['match_time'],
                'round' => $match['round'],
                'field_id' => $match['field_id'],
            ], $match);
        }
    }

    private function generateFixtures(array $teams, bool $roundTrip): array
    {
        $fixtures = [];
        $teamCount = count($teams);
        $rounds = $teamCount - 1;

        if ($teamCount % 2 !== 0) {
            $teams[] = null; // equipo fantasma
            $teamCount++;
            $rounds = $teamCount - 1;
        }

        $half = $teamCount / 2;
        $rotation = $teams;

        for ($round = 0; $round < $rounds; $round++) {
            $pairings = [];
            for ($i = 0; $i < $half; $i++) {
                $home = $rotation[$i];
                $away = $rotation[$teamCount - 1 - $i];

                if (!is_null($home) && !is_null($away)) {
                    $pairings[] = [$home, $away];
                }
            }

            $fixtures[] = $pairings;

            // rotación de equipos (excepto el primero)
            $fixed = array_shift($rotation);
            $last = array_pop($rotation);
            array_unshift($rotation, $fixed);
            array_splice($rotation, 1, 0, [$last]);
        }

        if ($roundTrip) {
            // agregar la vuelta: invertir local y visitante
            foreach ($fixtures as $round) {
                $reverseRound = array_map(fn($match) => [$match[1], $match[0]], $round);
                $fixtures[] = $reverseRound;
            }
        }

        return $fixtures;
    }

    private function generateAvailableSlots($fields, $startDate, $matchDuration, $leagueAvailabilities, $weeksToGenerate): array
    {
        $availableSlots = [];
        $scheduleDate = Carbon::parse($startDate);
        $daysToGenerate = $weeksToGenerate * 7;

        for ($i = 0; $i < $daysToGenerate; $i++) {
            foreach ($fields as $field) {
                $fieldAvailability = $field->availability;
                $leagueAvailability = $leagueAvailabilities[$field->field_id] ?? null;
                if (!$leagueAvailability) {
                    continue;
                }

                foreach ($fieldAvailability as $day => $schedule) {
                    if (!isset($schedule['enabled']) || !$schedule['enabled'] || !isset($schedule['intervals'])) {
                        continue;
                    }

                    foreach ($schedule['intervals'] as $interval) {
                        if (!$interval['selected']) {
                            continue;
                        }

                        if ($interval['value'] === '*') {
                            $start = Carbon::parse($scheduleDate)->setTime(
                                $leagueAvailability[$day]['start']['hours'],
                                $leagueAvailability[$day]['start']['minutes']
                            );
                            $end = Carbon::parse($scheduleDate)->setTime(
                                $leagueAvailability[$day]['end']['hours'],
                                $leagueAvailability[$day]['end']['minutes']
                            );
                        } else {
                            [$hour, $minute] = explode(':', $interval['value']);
                            $start = Carbon::parse($scheduleDate)->setTime($hour, $minute);
                            $end = $start->copy()->addMinutes(60);
                        }

                        while ($start->copy()->addMinutes($matchDuration)->lessThanOrEqualTo($end)) {
                            $availableSlots[] = [
                                'field_id' => $field->field_id,
                                'match_time' => $start->copy()
                            ];
                            $start->addMinutes($matchDuration);
                        }
                    }
                }
            }
            $scheduleDate->addDay();
        }

        return $availableSlots;
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
            $field['availability'] = array_filter($field['availability'], static fn($day) => $day['enabled'] ?? false);
            return $field;
        }, $data);
        foreach ($fields as $field) {
            $leagueField = LeagueField::where('field_id', $field['field_id'])->first();
            if (!$leagueField) {
                throw new RuntimeException("El campo {$field['field_name']} no tiene disponibilidad configurada en la liga.");
            }
            $leagueAvailability = $leagueField->availability;
            foreach ($field['availability'] as $day => $schedule) {
                if ($day === 'isCompleted' || !isset($schedule['intervals'])) {
                    continue;
                }
                if (!isset($leagueAvailability[$day]) || !$leagueAvailability[$day]['enabled']) {
                    throw new RuntimeException("El campo {$field['field_name']} no tiene disponibilidad configurada en la liga para el día $day.");
                }
                foreach ($schedule['intervals'] as $interval) {
                    if (!$interval['selected']) {
                        continue;
                    }
                    if ($interval['value'] === '*') {
                        // Reservar desde el inicio hasta el fin permitido por la liga
                        $requestedStart = (int)$leagueAvailability[$day]['start']['hours'] * 60 + (int)$leagueAvailability[$day]['start']['minutes'];
                        $requestedEnd = (int)$leagueAvailability[$day]['end']['hours'] * 60 + (int)$leagueAvailability[$day]['end']['minutes'];
                    } else {
                        // Interpretar el valor como una hora exacta (e.j. 09) y reservar una
                        [$hour, $minute] = explode(':', $interval['value']);
                        $requestedStart = (int)$hour * 60 + (int)$minute;
                        $requestedEnd = $requestedStart + 60; // asumir 1 hora de duración por bloque
                    }
                    $globalStart = (int)$leagueAvailability[$day]['start']['hours'] * 60 + (int)$leagueAvailability[$day]['start']['minutes'];
                    $globalEnd = (int)$leagueAvailability[$day]['end']['hours'] * 60 + (int)$leagueAvailability[$day]['end']['minutes'];


                    if ($requestedStart < $globalStart || $requestedEnd > $globalEnd) {
                        throw new RuntimeException(sprintf(
                            'El intervalo %s - %s del campo %s no está dentro del horario permitido %s:%s - %s:%s por la liga en el día %s.',
                            $interval['start'],
                            $interval['end'],
                            $field['field_name'],
                            $leagueAvailability[$day]['start']['hours'],
                            $leagueAvailability[$day]['start']['minutes'],
                            $leagueAvailability[$day]['end']['hours'],
                            $leagueAvailability[$day]['end']['minutes'],
                            self::DAYS[$day]
                        ));
                    }
                    // Validar solapamientos con otros torneos
                    $conflict = TournamentField::where('field_id', $field['field_id'])
                        ->whereJsonContains('availability', [$day])
                        ->get();
                    foreach ($conflict as $existingField) {
                        $existingAvailability = $existingField->availability;
                        if (!isset($existingAvailability[$day]['intervals'])) {
                            continue;
                        }
                        foreach ($existingAvailability[$day]['intervals'] as $existingInterval) {
                            [$exStartH, $exStartM] = explode(':', $existingInterval['start']);
                            [$exEndH, $exEndM] = explode(':', $existingInterval['end']);
                            $existingStart = (int)$exStartH * 60 + (int)$exStartM;
                            $existingEnd = (int)$exEndH * 60 + (int)$exEndM;

                            if (!($requestedEnd <= $existingStart || $requestedStart >= $existingEnd)) {
                                throw new RuntimeException(sprintf(
                                    'El intervalo %s - %s solicitado para el campo %s se cruza con %s - %s ya reservado por otro torneo en el día %s.',
                                    $interval['start'],
                                    $interval['end'],
                                    $field['field_name'],
                                    $existingInterval['start'],
                                    $existingInterval['end'],
                                    self::DAYS[$day]
                                ));
                            }
                        }
                    }
                }
            }

            // Guardar la disponibilidad final limpia por torneo
            TournamentField::updateOrCreate(
                ['field_id' => $field['field_id'], 'tournament_id' => $this->tournament->id],
                ['availability' => $field['availability']]
            );
        }

    }
}
