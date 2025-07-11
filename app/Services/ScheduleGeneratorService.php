<?php

namespace App\Services;

use App\Models\Game;
use App\Models\LeagueField;
use App\Models\Location;
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
    private const GLOBAL_REST = 15;
    private const UNEXPECTED_BUFFER = 15;
    private Tournament $tournament;

    public function setTournament(Tournament $tournament): self
    {
        $this->tournament = $tournament;
        return $this;
    }

    public function makeSchedule(): array
    {
        $config = $this->tournament->configuration;
        $gameTime = $config->game_time;
        $adminGap = $config->time_between_games;
        $matchDuration = $gameTime
            + self::GLOBAL_REST
            + $adminGap
            + self::UNEXPECTED_BUFFER;
        $teams = $this->tournament->teams()->pluck('teams.id')->toArray();
        $fields = TournamentField::where('tournament_id', $this->tournament->id)->get();

        // 1) elegir ida y vuelta según fase
        // asumiendo que el formato "liga" es tournament_format_id = 1
        $useRoundTrip = $config->tournament_format_id === 1
            ? $config->round_trip    // liga: usar ida y vuelta según este flag
            : $config->elimination_round_trip;  // otros formatos (elim.): usar este otro

        // 2) generar fixtures
        $fixturesByRound = $this->generateFixtures($teams, $useRoundTrip);
        $totalRounds = count($fixturesByRound);

        // 3) generar solo tantas semanas como rondas
        $weeksToGenerate = $totalRounds;

        // 4) slot availabilities
        $leagueAvailabilities = LeagueField::whereIn('field_id', $fields->pluck('field_id'))
            ->get()
            ->keyBy('field_id')
            ->map(fn($f) => $f->availability);

        $availableSlots = $this->generateAvailableSlots(
            $fields,
            $this->tournament->start_date,
            $matchDuration,
            $leagueAvailabilities,
            $weeksToGenerate
        );

        // 5) agrupar por semana y campo
        $startOfWeekDay = Carbon::parse($this->tournament->start_date)->dayOfWeek;
        $slotsByWeekAndField = collect($availableSlots)
            ->groupBy(fn($slot) => Carbon::parse($slot['match_time'])
                ->startOfWeek($startOfWeekDay)
                ->toDateString(),
            )->map(fn($week) => $week->groupBy('field_id'));

        // 6) encontrar primera semana válida
        $weekStart = $this->findFirstAvailableWeekStart(
            $slotsByWeekAndField->toArray(),
            Carbon::parse($this->tournament->start_date)
        );

        // 7) asignar partido por ronda en cada semana
        $matches = [];
        $teamRounds = [];
        $globalIndex = 0;
        foreach ($fixturesByRound as $round => $fixtures) {
            $weekKey = $weekStart->toDateString();
            if (!isset($slotsByWeekAndField[$weekKey])) {
                throw new RuntimeException("No hay disponibilidad para la semana de la jornada " . ($round + 1));
            }
            $weekFieldSlots = $slotsByWeekAndField[$weekKey];
            $fieldPointers = array_fill_keys(array_keys($weekFieldSlots->toArray()), 0);
            $fieldsAvailable = array_keys($weekFieldSlots->toArray());

            foreach ($fixtures as $pair) {
                $assigned = false;
                $attempts = 0;
                $idx = $globalIndex;
                while ($attempts < count($fieldsAvailable)) {
                    $fieldId = $fieldsAvailable[$idx % count($fieldsAvailable)];
                    $slots = $weekFieldSlots[$fieldId];
                    $ptr = $fieldPointers[$fieldId] ?? 0;

                    if (isset($slots[$ptr])) {
                        $slot = $slots[$ptr];
                        $fieldPointers[$fieldId]++;
                        $matchTime = $slot['match_time'];
                        $location = DB::table('fields')->where('id', $fieldId)->value('location_id');

                        // evitar doble partido equipo misma ronda
                        $matchRound = $round + 1;
                        if (isset($teamRounds[$matchRound][$pair[0]]) ||
                            isset($teamRounds[$matchRound][$pair[1]])
                        ) {
                            $idx++;
                            $attempts++;
                            continue;
                        }
                        $teamRounds[$matchRound][$pair[0]] = true;
                        $teamRounds[$matchRound][$pair[1]] = true;

                        $matches[] = [
                            'tournament_id' => $this->tournament->id,
                            'home_team_id' => $pair[0],
                            'away_team_id' => $pair[1],
                            'field_id' => $fieldId,
                            'location_id' => $location,
                            'match_date' => $matchTime->toDateString(),
                            'match_time' => $matchTime->format('H:i:s'),
                            'round' => $matchRound,
                            'status' => 'programado',
                        ];
                        $globalIndex++;
                        $assigned = true;
                        break;
                    }

                    $idx++;
                    $attempts++;
                }
                if (!$assigned) {
                    throw new RuntimeException("La cantidad de horas seleccionadas no son suficientes para generar completamente las jornadas del calendario. Por favor, ajuste la disponibilidad de los campos o el número de equipos.");
                }
            }

            // avanzar a la siguiente semana
            $weekStart->addWeek();
        }

        return $matches;
    }


    public function persistScheduleToMatchSchedules(array $matches): void
    {
        // Fase activa del torneo
        $phase = TournamentPhase::where('tournament_id', $matches[0]['tournament_id'])
            ->where('is_active', true)
            ->first();

        DB::transaction(function () use ($matches, $phase) {
            foreach ($matches as $match) {
                // 1) Crear o actualizar el partido
                $game = Game::updateOrCreate(
                    [
                        'tournament_id' => $match['tournament_id'],
                        'home_team_id' => $match['home_team_id'],
                        'away_team_id' => $match['away_team_id'],
                        'match_date' => $match['match_date'],
                        'match_time' => $match['match_time'],
                        'round' => $match['round'],
                        'field_id' => $match['field_id'],
                        'league_id' => auth()->user()->league->id,
                    ],
                    array_merge($match, [
                        'tournament_phase_id' => $phase?->id,
                    ])
                );

                // 2) Marcar en_use=true en tournament_fields
                $tournField = TournamentField::where('tournament_id', $match['tournament_id'])
                    ->where('field_id', $match['field_id'])
                    ->first();

                if (!$tournField) {
                    throw new RuntimeException("El campo {$match['field_id']} no está configurado en el torneo.");
                }

                $availability = $tournField->availability;
                // Día de la semana en minúsculas
                $day = strtolower(Carbon::parse($match['match_date'])->format('l'));

                // Franjas existentes
                $intervals = $availability[$day]['intervals'] ?? [];

                // Duración total del bloque (game_time + gaps + buffers)
                $config = $tournField->tournament->configuration;
                $duration = $config->game_time
                    + $config->time_between_games
                    + self::GLOBAL_REST
                    + self::UNEXPECTED_BUFFER;

                // Horario de inicio y siguiente bloque
                $startTime = substr($match['match_time'], 0, 5);
                $nextStart = Carbon::createFromFormat('H:i', $startTime)
                    ->addMinutes($duration)
                    ->format('H:i');

                // Marcar todos los slots que caen dentro de la duración del partido
                $startMins = Carbon::createFromFormat('H:i', $startTime)->hour * 60
                    + Carbon::createFromFormat('H:i', $startTime)->minute;
                $endLimitMins = $startMins + $duration;

                foreach ($intervals as &$interval) {
                    // parsear valor de interval.value a minutos desde medianoche
                    [$h, $m] = explode(':', $interval['value']);
                    $tMins = (int)$h * 60 + (int)$m;

                    // si el inicio del slot está dentro del rango [start, start+duración)
                    if ($tMins >= $startMins && $tMins < $endLimitMins) {
                        $interval['in_use'] = true;
                        $interval['selected'] = true;
                    }
                }
                unset($interval);

                // Guardar cambios de availability
                $availability[$day]['intervals'] = $intervals;
                $tournField->availability = $availability;
                $tournField->save();
            }
        });
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
                    if ($round % 2 !== 0) {
                        [$home, $away] = [$away, $home];
                    }
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
                $fieldId = $field->field_id;
                $tournSched = $field->availability;
                $leagueAvail = $leagueAvailabilities[$fieldId] ?? null;
                if (!$leagueAvail) {
                    continue;
                }

                // día de la semana en minúsculas, ej. 'monday'
                $day = strtolower($scheduleDate->format('l'));

                // disponibilidad para este torneo en ese día
                if (empty($tournSched[$day]['enabled'])) {
                    continue;
                }
                $intervals = collect($tournSched[$day]['intervals'])
                    ->filter(fn($int) => !empty($int['selected']));

                if ($intervals->isEmpty()) {
                    continue;
                }

                // límite global de la liga
                $leagueEnd = $scheduleDate->copy()
                    ->setTime(
                        $leagueAvail[$day]['end']['hours'],
                        $leagueAvail[$day]['end']['minutes']
                    );

                // caso “todo el día”
                if ($intervals->first()['value'] === '*') {
                    $blockStart = $scheduleDate->copy()
                        ->setTime(
                            $leagueAvail[$day]['start']['hours'],
                            $leagueAvail[$day]['start']['minutes']
                        );
                    $blockEnd = $leagueEnd;
                } else {
                    // 1) convertir cada value ('09:00','10:00',...) en un Carbon
                    $times = $intervals->map(fn($int) => $scheduleDate->copy()->setTime(
                        ...explode(':', $int['value'])
                    )
                    );
                    // 2) rango desde el mínimo hasta el máximo + matchDuration
                    $blockStart = $times->min();
                    $blockEnd = $times->max()->copy()->addMinutes($matchDuration);
                    // 3) no pasarnos de la liga
                    if ($blockEnd->greaterThan($leagueEnd)) {
                        $blockEnd = $leagueEnd;
                    }
                }

                // finalmente, generar slots con paso = matchDuration
                $cursor = $blockStart->copy();
                while ($cursor->copy()->addMinutes($matchDuration)->lessThanOrEqualTo($blockEnd)) {
                    $availableSlots[] = [
                        'field_id' => $fieldId,
                        'match_time' => $cursor->copy(),
                    ];
                    $cursor->addMinutes($matchDuration);
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
            ->where('league_id', auth()->user()->league_id)
            ->firstOrFail();
        $startDate = Carbon::parse($generalData['start_date']);

        if ($startDate->isPast()) {
            throw new RuntimeException('La fecha de inicio no puede ser en el pasado.');
        }
        $tournament->start_date = $startDate;
        $tournament->saveQuietly();
        $this->tournament = $tournament->refresh();
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
                    'elimination_round_trip' => $generalData['elimination_round_trip'] ?? false,
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
            $this->tournament->tournamentPhases()->save(
                TournamentPhase::updateOrCreate(
                    ['tournament_id' => $this->tournament->id, 'phase_id' => $eliminationPhase['id']],
                    $eliminationPhase
                )
            );
        }
    }

    private function saveFieldsPhase($data): void
    {
        $config = $this->tournament->configuration;
        $gameTime = $config->game_time;
        $adminGap = $config->time_between_games;
        $blockDuration = $gameTime
            + self::GLOBAL_REST
            + $adminGap
            + self::UNEXPECTED_BUFFER;
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
                        $requestedEnd = $requestedStart + $blockDuration;
                    }
                    // Validar solapamientos con otros torneos
                    $this->validateNoTimeConflict($field['field_id'], $day, $requestedStart, $requestedEnd, $field['field_name']);
                }
            }

            // Guardar la disponibilidad final limpia por torneo
            TournamentField::updateOrCreate(
                ['field_id' => $field['field_id'], 'tournament_id' => $this->tournament->id],
                ['availability' => $field['availability']]
            );
        }
    }

    private function findFirstAvailableWeekStart(array $slotsByWeekAndField, Carbon $startDate): Carbon
    {
        $start = $startDate->copy()->startOfWeek($startDate->dayOfWeek);
        for ($i = 0; $i < 4; $i++) {
            if (isset($slotsByWeekAndField[$start->toDateString()])) {
                return $start;
            }
            $start->addWeek();
        }
        throw new RuntimeException("No se encontró una semana válida con disponibilidad para iniciar el torneo.");
    }

    private function validateNoTimeConflict(int $fieldId, string $day, int $requestedStart, int $requestedEnd, string $fieldName): void
    {
        $conflictFields = TournamentField::where('field_id', $fieldId)
            ->where('tournament_id', '!=', $this->tournament->id)
            ->get();

        foreach ($conflictFields as $existingField) {
            $existingAvailability = $existingField->availability;

            if (!isset($existingAvailability[$day]['intervals'])) {
                continue;
            }

            foreach ($existingAvailability[$day]['intervals'] as $existingInterval) {
                if (!$existingInterval['selected']) {
                    continue;
                }
                $slotValue = $existingInterval['value'];
                $startTime = (string)$slotValue;
                $endTime = Carbon::createFromFormat('H:i', $startTime)
                    ?->addMinutes(60)
                    ->format('H:i');
                // 2) lo convertimos a minutos o Carbon para tu lógica de conflicto
                [$exStartH, $exStartM] = explode(':', $startTime);
                [$exEndH, $exEndM] = explode(':', $endTime);

                $existingStart = $exStartH * 60 + $exStartM;
                $existingEnd = $exEndH * 60 + $exEndM;

                if (!($requestedEnd <= $existingStart || $requestedStart >= $existingEnd)) {
                    throw new RuntimeException(sprintf(
                        'El intervalo %s - %s solicitado para el campo %s se cruza con %s - %s ya reservado por otro torneo en el día %s.',
                        $this->formatTime($requestedStart),
                        $this->formatTime($requestedEnd),
                        $fieldName,
                        $existingStart,
                        $existingEnd,
                        config('constants.label_days')[$day]
                    ));
                }
            }
        }
    }

    private function formatTime(int $minutes): string
    {
        $h = floor($minutes / 60);
        $m = $minutes % 60;
        return sprintf('%02d:%02d', $h, $m);
    }


}
