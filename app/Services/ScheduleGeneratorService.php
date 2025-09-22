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
use App\Services\GroupConfigurationOptionService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use TournamentFormatId;

class ScheduleGeneratorService
{
    private const int GLOBAL_REST = 15;
    private const int UNEXPECTED_BUFFER = 15;
    private Tournament $tournament;
    private bool $forceGroupStage = false;


    public function setTournament(Tournament $tournament): self
    {
        $this->tournament = $tournament;
        return $this;
    }

    public function enableGroupStageMode(bool $enable = true): self
    {
        $this->forceGroupStage = $enable;
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

        // 1) elegir ida y vuelta según fase (liga usa TournamentFormatId::League)
        $useRoundTrip = (int)$config->tournament_format_id === TournamentFormatId::League->value
            ? $config->round_trip    // liga: usar ida y vuelta según este flag
            : $config->elimination_round_trip;  // otros formatos (elim.): usar este

        // 2) según formato: liga pura vs liga+eliminatoria (fase grupos)
        $formatId = (int)$config->tournament_format_id;
        $groupStageEnabled = (bool)($config->group_stage ?? false);
        $activePhase = $this->tournament->tournamentPhases()->where('is_active', true)->with('phase')->first();
        $activePhaseName = $activePhase?->phase?->name;

        if ($formatId !== TournamentFormatId::League->value && (\App\Models\TournamentGroupConfiguration::where('tournament_id', $this->tournament->id)->exists() || $this->forceGroupStage)) {
            if ($groupStageEnabled || $this->forceGroupStage) {
                // Liga + Eliminatoria con grupos activos: generar fase de grupos
                return $this->makeGroupStageScheduleInternal($teams, $fields, $matchDuration);
            }
            // Si no hay grupos habilitados, y es una fase de eliminación
            if (in_array($activePhaseName, ['Dieciseisavos de Final','Octavos de Final','Cuartos de Final','Semifinales','Final'])) {
                return $this->makeEliminationScheduleInternal($fields, $matchDuration, $activePhase);
            }
        }

        // Liga pura: generar fixtures globales
        $fixturesByRound = $this->generateFixtures($teams, $useRoundTrip);
        $totalRounds = count($fixturesByRound);
        $weeksToGenerate = $totalRounds; // 3) una semana por ronda


        $availableSlots = $this->generateAvailableSlots(
            $fields,
            $this->tournament->start_date,
            $matchDuration,
            $weeksToGenerate
        );

        // 5) agrupar por semana y campo
        $startOfWeekDay = Carbon::parse($this->tournament->start_date)->dayOfWeek;
        $slotsByWeekAndField = collect($availableSlots)
            ->groupBy(fn($slot) => $slot['match_time']
                ->copy()
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

                        // evitar doble partido equipo en misma ronda
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

    private function makeGroupStageScheduleInternal(array $teams, $fields, int $matchDuration): array
    {
        $gc = $this->tournament->groupConfiguration;
        $groupSizes = null;
        if ($gc && is_array($gc->group_sizes) && !empty($gc->group_sizes)) {
            $normalized = array_values(array_filter(
                array_map('intval', $gc->group_sizes ?? []),
                static fn($size) => $size > 0
            ));
            if (!empty($normalized)) {
                $groupSizes = $normalized;
            }
        }
        $teamsPerGroup = (int)($gc?->teams_per_group ?? 0);
        if ($teamsPerGroup <= 0 && $groupSizes) {
            $teamsPerGroup = max($groupSizes);
        }
        // 1) Asignar grupos y persistir en pivot team_tournament.group_key
        $groups = $this->assignGroups($teams, $teamsPerGroup, $groupSizes);
        $this->persistGroups($groups);

        // 2) Generar fixtures por grupo (una vuelta por defecto)
        $groupFixtures = [];
        $maxRounds = 0;
        foreach ($groups as $groupKey => $groupTeams) {
            $fx = $this->generateFixtures($groupTeams, false);
            $groupFixtures[$groupKey] = $fx; // array de rondas
            $maxRounds = max($maxRounds, count($fx));
        }

        // 3) Intercalar rondas: semana 1 = ronda 1 de todos los grupos, etc.
        $availableSlots = $this->generateAvailableSlots(
            $fields,
            $this->tournament->start_date,
            $matchDuration,
            $maxRounds
        );

        // 4) agrupar slots por semana y campo
        $startOfWeekDay = Carbon::parse($this->tournament->start_date)->dayOfWeek;
        $slotsByWeekAndField = collect($availableSlots)
            ->groupBy(fn($slot) => $slot['match_time']
                ->copy()
                ->startOfWeek($startOfWeekDay)
                ->toDateString())
            ->map(fn($week) => $week->groupBy('field_id'));

        $weekStart = $this->findFirstAvailableWeekStart(
            $slotsByWeekAndField->toArray(),
            Carbon::parse($this->tournament->start_date)
        );

        $matches = [];
        $teamRounds = [];
        $globalIndex = 0;
        for ($round = 0; $round < $maxRounds; $round++) {
            $weekKey = $weekStart->toDateString();
            if (!isset($slotsByWeekAndField[$weekKey])) {
                throw new RuntimeException("No hay disponibilidad para la semana de la jornada " . ($round + 1) . " (fase de grupos)");
            }
            $weekFieldSlots = $slotsByWeekAndField[$weekKey];
            $fieldPointers = array_fill_keys(array_keys($weekFieldSlots->toArray()), 0);
            $fieldsAvailable = array_keys($weekFieldSlots->toArray());

            // Combinar partidos de la misma ronda en todos los grupos
            $fixturesThisRound = [];
            foreach ($groupFixtures as $gKey => $fx) {
                if (isset($fx[$round])) {
                    foreach ($fx[$round] as $pair) {
                        $fixturesThisRound[] = [$pair[0], $pair[1]];
                    }
                }
            }

            foreach ($fixturesThisRound as $pair) {
                $assigned = false;
                $attempts = 0; $idx = $globalIndex;
                while ($attempts < max(1, count($fieldsAvailable))) {
                    $fieldId = $fieldsAvailable[$idx % count($fieldsAvailable)];
                    $slots = $weekFieldSlots[$fieldId] ?? collect();
                    $ptr = $fieldPointers[$fieldId] ?? 0;
                    if (isset($slots[$ptr])) {
                        $slot = $slots[$ptr];
                        $fieldPointers[$fieldId] = $ptr + 1;
                        $matchTime = $slot['match_time'];
                        $location = DB::table('fields')->where('id', $fieldId)->value('location_id');

                        $matchRound = $round + 1;
                        if (isset($teamRounds[$matchRound][$pair[0]]) || isset($teamRounds[$matchRound][$pair[1]])) {
                            $idx++; $attempts++; continue;
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
                        $assigned = true; break;
                    }
                    $idx++; $attempts++;
                }
                if (!$assigned) {
                    throw new RuntimeException('No hay suficientes horas/espacios para agendar todos los partidos de grupos. Ajusta disponibilidad o número de equipos.');
                }
            }

            $weekStart->addWeek();
        }
        return $matches;
    }

    private function makeEliminationScheduleInternal($fields, int $matchDuration, $activePhase): array
    {
        $formatId = (int)$this->tournament->configuration->tournament_format_id;
        $phaseName = $activePhase?->phase?->name;
        $targetTeams = match($phaseName) {
            'Dieciseisavos de Final' => 32,
            'Octavos de Final' => 16,
            'Cuartos de Final' => 8,
            'Semifinales' => 4,
            'Final' => 2,
            default => 8,
        };

        $qualifiers = [];
        if ($formatId === TournamentFormatId::GroupAndElimination->value) {
            // Clasificados desde standings de la fase de grupos
            $groupPhase = $this->tournament->tournamentPhases()
                ->whereHas('phase', fn($q) => $q->where('name','Fase de grupos'))
                ->first();
            if (!$groupPhase) {
                throw new RuntimeException('No existe la fase de grupos para calcular clasificados.');
            }
            $gc = $this->tournament->groupConfiguration;
            if (!$gc) {
                throw new RuntimeException('No hay configuración de grupos.');
            }

            $groupRows = DB::table('standings')
                ->join('team_tournament','team_tournament.id','=','standings.team_tournament_id')
                ->where('standings.tournament_id', $this->tournament->id)
                ->where('standings.tournament_phase_id', $groupPhase->id)
                ->select(['standings.team_id','standings.points','standings.goal_difference','standings.goals_for','standings.rank','team_tournament.group_key'])
                ->orderBy('team_tournament.group_key')
                ->orderBy('standings.rank')
                ->get();
            if ($groupRows->isEmpty()) {
                throw new RuntimeException('No hay standings de grupos calculados. Completa los partidos y recalcula.');
            }
            $groups = $groupRows->groupBy('group_key');
            $thirdCandidates = [];
            foreach ($groups as $rows) {
                $topN = $rows->sortBy('rank')->take($gc->advance_top_n)->pluck('team_id')->all();
                array_push($qualifiers, ...$topN);
                if ($gc->include_best_thirds) {
                    $third = $rows->firstWhere('rank', $gc->advance_top_n + 1);
                    if ($third) { $thirdCandidates[] = $third; }
                }
            }
            if ($gc->include_best_thirds && $gc->best_thirds_count) {
                usort($thirdCandidates, function($a,$b){
                    return ($b->points <=> $a->points) ?: ($b->goal_difference <=> $a->goal_difference) ?: ($b->goals_for <=> $a->goals_for);
                });
                $best = array_slice(array_map(fn($r) => $r->team_id, $thirdCandidates), 0, (int)$gc->best_thirds_count);
                array_push($qualifiers, ...$best);
            }
            // Ordenación global por métricas de grupo
            $seedMetrics = DB::table('standings')
                ->where('tournament_id', $this->tournament->id)
                ->where('tournament_phase_id', $groupPhase->id)
                ->whereIn('team_id', $qualifiers)
                ->get(['team_id','points','goal_difference','goals_for'])
                ->keyBy('team_id');
            usort($qualifiers, function($a,$b) use ($seedMetrics){
                $A = $seedMetrics[$a] ?? null; $B = $seedMetrics[$b] ?? null;
                if (!$A && !$B) return 0; if (!$A) return 1; if (!$B) return -1;
                return ($B->points <=> $A->points) ?: ($B->goal_difference <=> $A->goal_difference) ?: ($B->goals_for <=> $A->goals_for);
            });
            $qualifiers = array_slice($qualifiers, 0, $targetTeams);
        } else {
            // Liga + Eliminatoria: tomar top N de la Tabla general
            $tablePhase = $this->tournament->tournamentPhases()
                ->whereHas('phase', fn($q) => $q->where('name','Tabla general'))
                ->first();
            if (!$tablePhase) {
                throw new RuntimeException('No existe la fase "Tabla general" para calcular clasificados.');
            }
            $rows = DB::table('standings')
                ->where('tournament_id', $this->tournament->id)
                ->where('tournament_phase_id', $tablePhase->id)
                ->orderBy('rank')
                ->limit($targetTeams)
                ->pluck('team_id')
                ->all();
            $qualifiers = $rows;
        }

        $pairs = [];
        $n = count($qualifiers);
        for ($i=0; $i < intdiv($n,2); $i++) {
            $high = $qualifiers[$i];
            $low  = $qualifiers[$n - 1 - $i];
            $pairs[] = [$high, $low];
        }

        // 2) reglas de la fase
        $rules = $activePhase->rules; // TournamentPhaseRule or null
        $roundTrip = (bool)($rules->round_trip ?? false);

        // 3) slots por semana
        $weeksToGenerate = $roundTrip ? 2 : 1;
        $availableSlots = $this->generateAvailableSlots($fields, $this->tournament->start_date, $matchDuration, $weeksToGenerate);
        $startOfWeekDay = Carbon::parse($this->tournament->start_date)->dayOfWeek;
        $slotsByWeekAndField = collect($availableSlots)
            ->groupBy(fn($slot) => $slot['match_time']
                ->copy()
                ->startOfWeek($startOfWeekDay)
                ->toDateString())
            ->map(fn($week) => $week->groupBy('field_id'));
        $weekStart = $this->findFirstAvailableWeekStart($slotsByWeekAndField->toArray(), Carbon::parse($this->tournament->start_date));

        $matches = [];
        $globalIndex = 0;
        $legs = $roundTrip ? 2 : 1;
        for ($leg = 1; $leg <= $legs; $leg++) {
            $weekKey = $weekStart->toDateString();
            if (!isset($slotsByWeekAndField[$weekKey])) {
                throw new RuntimeException('No hay disponibilidad suficiente para programar las eliminatorias.');
            }
            $weekFieldSlots = $slotsByWeekAndField[$weekKey];
            $fieldPointers = array_fill_keys(array_keys($weekFieldSlots->toArray()), 0);
            $fieldsAvailable = array_keys($weekFieldSlots->toArray());

            foreach ($pairs as [$seedHigh,$seedLow]) {
                $assigned = false; $attempts = 0; $idx = $globalIndex;
                while ($attempts < max(1, count($fieldsAvailable))) {
                    $fieldId = $fieldsAvailable[$idx % count($fieldsAvailable)];
                    $slots = $weekFieldSlots[$fieldId] ?? collect();
                    $ptr = $fieldPointers[$fieldId] ?? 0;
                    if (isset($slots[$ptr])) {
                        $slot = $slots[$ptr];
                        $fieldPointers[$fieldId] = $ptr + 1;
                        $matchTime = $slot['match_time'];
                        $location = DB::table('fields')->where('id', $fieldId)->value('location_id');

                        // Evitar choque con cualquier juego ya asignado
                        $exists = DB::table('games')
                            ->where('field_id', $fieldId)
                            ->whereDate('match_date', $matchTime->toDateString())
                            ->where('match_time', $matchTime->format('H:i:s'))
                            ->exists();
                        if ($exists) { $idx++; $attempts++; continue; }

                        // Definir localía: ida = low recibe, vuelta = high recibe
                        $home = ($leg === 1) ? $seedLow : $seedHigh;
                        $away = ($leg === 1) ? $seedHigh : $seedLow;

                        $matches[] = [
                            'tournament_id' => $this->tournament->id,
                            'home_team_id' => $home,
                            'away_team_id' => $away,
                            'field_id' => $fieldId,
                            'location_id' => $location,
                            'match_date' => $matchTime->toDateString(),
                            'match_time' => $matchTime->format('H:i:s'),
                            'round' => $leg, // ida/vuelta como rounds 1 y 2
                            'status' => 'programado',
                        ];
                        $globalIndex++;
                        $assigned = true; break;
                    }
                    $idx++; $attempts++;
                }
                if (!$assigned) {
                    throw new RuntimeException('No hay suficientes horas/espacios para programar todas las llaves.');
                }
            }
            // siguiente semana para la vuelta
            $weekStart->addWeek();
        }
        return $matches;
    }

    private function assignGroups(array $teams, int $teamsPerGroup, ?array $groupSizes = null): array
    {
        sort($teams); // orden estable
        $groups = [];
        $letters = range('A', 'Z');
        $groupIndex = 0;

        if (is_array($groupSizes) && !empty($groupSizes)) {
            $normalizedSizes = array_values(array_filter(
                array_map('intval', $groupSizes),
                static fn($size) => $size > 0
            ));

            $assignedTeams = 0;
            $totalTeams = count($teams);

            if (count($normalizedSizes) === 1 && $normalizedSizes[0] === $totalTeams) {
                throw new RuntimeException('La configuración de grupos es inválida: debe haber al menos dos grupos.');
            }
            foreach ($normalizedSizes as $size) {
                if ($size < 3 || $size > 6) {
                    throw new RuntimeException('La configuración de grupos es inválida: cada grupo debe tener entre 3 y 6 equipos.');
                }
                $letter = $letters[$groupIndex] ?? ('G' . ($groupIndex + 1));
                $slice = array_slice($teams, $assignedTeams, $size);

                if (count($slice) !== $size) {
                    throw new RuntimeException('No hay suficientes equipos para llenar los grupos configurados.');
                }

                $groups[$letter] = $slice;
                $assignedTeams += $size;
                $groupIndex++;
            }

            if ($assignedTeams !== $totalTeams) {
                throw new RuntimeException('La suma de los tamaños de grupo no coincide con el total de equipos.');
            }

            return $groups; // ['A'=>[...], 'B'=>[...]]
        }
        if ($teamsPerGroup < 3 || $teamsPerGroup > 6) {
            throw new RuntimeException('La configuración de grupos es inválida: debe haber entre 3 y 6 equipos por grupo.');
        }

        $totalTeams = count($teams);
        if ($teamsPerGroup === $totalTeams) {
            throw new RuntimeException('La configuración de grupos es inválida: debe haber al menos dos grupos.');
        }

        $i = 0;
        foreach ($teams as $teamId) {
            $letter = $letters[$groupIndex] ?? ('G' . ($groupIndex + 1));
            $groups[$letter][] = $teamId;
            $i++;
            if ($i % $teamsPerGroup === 0) {
                $groupIndex++;
            }
        }

        return $groups; // ['A'=>[...], 'B'=>[...]]
    }

    private function persistGroups(array $groups): void
    {
        foreach ($groups as $letter => $teamIds) {
            DB::table('team_tournament')
                ->where('tournament_id', $this->tournament->id)
                ->whereIn('team_id', $teamIds)
                ->update(['group_key' => $letter, 'updated_at' => now()]);
        }
    }


    public function persistScheduleToMatchSchedules(array $matches): void
    {
        // Fase activa del torneo
        $phase = TournamentPhase::where('tournament_id', $matches[0]['tournament_id'])
            ->where('is_active', true)
            ->first();

        $leagueTz = $this->tournament->league->timezone ?? config('app.timezone', 'America/Mexico_City');
        $config = $this->tournament->configuration;
        $matchDuration = ($config->game_time ?? 0)
            + self::GLOBAL_REST
            + ($config->time_between_games ?? 0)
            + self::UNEXPECTED_BUFFER;

        DB::transaction(static function () use ($matches, $phase, $leagueTz, $matchDuration) {
            foreach ($matches as $match) {
                // Calcular UTC a partir de fecha/hora local de liga
                $localStart = \Carbon\Carbon::parse($match['match_date'] . ' ' . $match['match_time'], $leagueTz);
                $startsAtUtc = $localStart->clone()->setTimezone('UTC');
                $endsAtUtc = $startsAtUtc->clone()->addMinutes($matchDuration);
                Game::updateOrCreate(
                    [
                        'tournament_id' => $match['tournament_id'],
                        'home_team_id'  => $match['home_team_id'],
                        'away_team_id'  => $match['away_team_id'],
                        'match_date'    => $match['match_date'],
                        'match_time'    => $match['match_time'],
                        'round'         => $match['round'],
                        'field_id'      => $match['field_id'],
                        'league_id'     => auth()->user()->league->id,
                    ],
                    array_merge($match, [
                        'tournament_phase_id' => $phase?->id,
                        'starts_at_utc' => $startsAtUtc,
                        'ends_at_utc' => $endsAtUtc,
                    ])
                );
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
                $reverseRound = array_map(static fn($match) => [$match[1], $match[0]], $round);
                $fixtures[] = $reverseRound;
            }
        }

        return $fixtures;
    }

    private function generateAvailableSlots($fields, $startDate, $matchDuration, $weeksToGenerate): array
    {
        $availableSlots = [];
        $leagueTz = $this->tournament->league->timezone ?? config('app.timezone', 'America/Mexico_City');
        $scheduleDate = Carbon::parse($startDate, $leagueTz);
        $daysToGenerate = $weeksToGenerate * 7;
        $leagueFields = LeagueField::whereIn('field_id', $fields->pluck('field_id'))
            ->where('league_id', auth()->user()->league_id)
            ->get()
            ->keyBy('field_id');
        for ($i = 0; $i < $daysToGenerate; $i++) {
            $dow = $scheduleDate->dayOfWeek; // 0..6

            foreach ($fields as $field) {
                $fieldId = $field->field_id;
                $lf = $leagueFields->get($fieldId);
                if (!$lf) {
                    continue;
                }

                // Reservas del torneo para ese día/campo
                $reservations = DB::table('tournament_field_reservations')
                    ->where('tournament_id', $this->tournament->id)
                    ->where('league_field_id', $lf->id)
                    ->where('day_of_week', $dow)
                    ->orderBy('start_minute')
                    ->get();

                foreach ($reservations as $res) {
                    // Generar slots con paso = matchDuration dentro del rango [start, end)
                    $cursor = $scheduleDate->copy()->startOfDay()->addMinutes((int)$res->start_minute);
                    $end    = $scheduleDate->copy()->startOfDay()->addMinutes((int)$res->end_minute);

                    while ($cursor->copy()->addMinutes($matchDuration)->lessThanOrEqualTo($end)) {
                        $availableSlots[] = [
                            'field_id'   => $fieldId,
                            'match_time' => $cursor->copy(),
                        ];
                        $cursor->addMinutes($matchDuration);
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
            'round_trip' => $data['rules_phase']['round_trip'],
            'group_stage' => $data['rules_phase']['group_stage'] ?? false,
            'elimination_round_trip' => $data['elimination_phase']['elimination_round_trip']
        ]));
        $this->saveTiebreakers($data['rules_phase']['tiebreakers']);
        $this->saveEliminationPhase($data['elimination_phase']);
        if (!empty($data['group_phase'])) {
            $this->saveGroupPhaseConfiguration(
                $data['group_phase'],
                (int)$data['general']['total_teams']
            );
        }
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
            $tp = TournamentPhase::updateOrCreate(
                ['tournament_id' => $this->tournament->id, 'phase_id' => $eliminationPhase['id']],
                [
                    'is_active' => $eliminationPhase['is_active'],
                    'is_completed' => $eliminationPhase['is_completed']
                ]
            );
            // Guardar reglas por fase si vienen
            if (!empty($eliminationPhase['rules']) && is_array($eliminationPhase['rules'])) {
                $rules = $eliminationPhase['rules'];
                \App\Models\TournamentPhaseRule::updateOrCreate(
                    ['tournament_phase_id' => $tp->id],
                    [
                        'round_trip' => (bool)($rules['round_trip'] ?? false),
                        'away_goals' => (bool)($rules['away_goals'] ?? false),
                        'extra_time' => array_key_exists('extra_time', $rules) ? (bool)$rules['extra_time'] : true,
                        'penalties' => array_key_exists('penalties', $rules) ? (bool)$rules['penalties'] : true,
                        'advance_if_tie' => $rules['advance_if_tie'] ?? 'better_seed',
                    ]
                );
            }
            $this->tournament->tournamentPhases()->save($tp);
        }
    }

    private function saveGroupPhaseConfiguration(array $data, int $totalTeams): void
    {
        $optionId = $data['option_id'] ?? null;
        if (!$optionId && isset($data['selected_option'])) {
            $optionId = is_array($data['selected_option'])
                ? ($data['selected_option']['id'] ?? null)
                : $data['selected_option'];
        }
        if (!$optionId && isset($data['option']) && !is_array($data['option'])) {
            $optionId = $data['option'];
        }

        $payload = null;
        if ($optionId) {
            $service = new GroupConfigurationOptionService();
            $options = $service->buildOptions($totalTeams);
            $matched = collect($options)->firstWhere('id', $optionId);
            if (!$matched) {
                throw new RuntimeException('La opción de grupos seleccionada no es válida para el total de equipos.');
            }
            $payload = $matched['group_phase'];
        } else {
            $payload = [
                'teams_per_group' => $data['teams_per_group'] ?? null,
                'advance_top_n' => $data['advance_top_n'] ?? null,
                'include_best_thirds' => $data['include_best_thirds'] ?? false,
                'best_thirds_count' => $data['best_thirds_count'] ?? null,
                'group_sizes' => $data['group_sizes'] ?? null,
            ];
        }

        if (!isset($payload['teams_per_group'], $payload['advance_top_n'])) {
            throw new RuntimeException('La configuración de grupos es inválida.');
        }

        $groupSizes = null;
        if (isset($payload['group_sizes']) && is_array($payload['group_sizes'])) {
            $normalized = array_values(array_filter(
                array_map('intval', $payload['group_sizes']),
                static fn($size) => $size > 0
            ));
            if (!empty($normalized)) {
                $groupSizes = $normalized;
            }
        }

        \App\Models\TournamentGroupConfiguration::updateOrCreate(
            ['tournament_id' => $this->tournament->id],
            [
                'teams_per_group' => (int)$payload['teams_per_group'],
                'advance_top_n' => (int)$payload['advance_top_n'],
                'include_best_thirds' => (bool)($payload['include_best_thirds'] ?? false),
                'best_thirds_count' => isset($payload['best_thirds_count']) && $payload['best_thirds_count'] !== null
                    ? (int)$payload['best_thirds_count']
                    : null,
                'group_sizes' => $groupSizes,
            ]
        );
    }
    private function dayNameToIndex(string $day): int
    {
        $map = [
            'sunday' => 0,
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
        ];
        $key = strtolower($day);
        if (!array_key_exists($key, $map)) {
            throw new RuntimeException("Día inválido: $day");
        }
        return $map[$key];
    }
    private function hmToMinute(string $hm): int
    {
        [$h, $m] = explode(':', $hm);
        return ((int)$h) * 60 + ((int)$m);
    }
    private function minuteToHM(int $minutes): string
    {
        $h = floor($minutes / 60);
        $m = $minutes % 60;
        return sprintf('%02d:%02d', $h, $m);
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

        foreach ($data as $field) {
            // Ubicar el league_field del campo en la liga actual
            $leagueField = LeagueField::where('field_id', $field['field_id'])
                ->where('league_id', auth()->user()->league_id)
                ->first();

            if (!$leagueField) {
                throw new RuntimeException("El campo {$field['field_name']} no está registrado en la liga.");
            }

            // Asegurar registro en tournament_fields para este campo
            TournamentField::updateOrCreate(
                ['tournament_id' => $this->tournament->id, 'field_id' => $field['field_id']],
                []
            );

            // Limpiar reservas previas de este torneo en este campo de liga
            DB::table('tournament_field_reservations')
                ->where('tournament_id', $this->tournament->id)
                ->where('league_field_id', $leagueField->id)
                ->delete();

            // Validar y crear reservas por día
            foreach ($field['availability'] as $day => $schedule) {
                if ($day === 'isCompleted' || empty($schedule['enabled'])) {
                    continue;
                }

                $dayOfWeek = $this->dayNameToIndex($day);

                // Ventanas permitidas por la liga para ese día
                $leagueWindows = DB::table('league_field_windows')
                    ->where('league_field_id', $leagueField->id)
                    ->where('day_of_week', $dayOfWeek)
                    ->where('enabled', true)
                    ->orderBy('start_minute')
                    ->get();

                if ($leagueWindows->isEmpty()) {
                    throw new RuntimeException("El campo {$field['field_name']} no tiene disponibilidad configurada en la liga para el día $day.");
                }

                // Caso “todo el día”
                $hasAllDay = collect($schedule['intervals'] ?? [])->first(fn($i) => ($i['value'] ?? null) === '*');

                if ($hasAllDay) {
                    // Reservar todas las ventanas permitidas por la liga para ese día
                    foreach ($leagueWindows as $lw) {
                        DB::table('tournament_field_reservations')->insert([
                            'tournament_id' => $this->tournament->id,
                            'league_field_id' => $leagueField->id,
                            'day_of_week' => $dayOfWeek,
                            'start_minute' => $lw->start_minute,
                            'end_minute' => $lw->end_minute,
                            'exclusive' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                    continue;
                }

                // Horas seleccionadas (p.ej. 09:00, 10:00, ...). Las colapsamos en 1 bloque continuo
                $selected = collect($schedule['intervals'] ?? [])
                    ->filter(fn($i) => !empty($i['selected']) && isset($i['value']) && $i['value'] !== '*')
                    ->map(fn($i) => $this->hmToMinute((string)$i['value']))
                    ->sort()
                    ->values();

                if ($selected->isEmpty()) {
                    // no se seleccionó nada para este día
                    continue;
                }

                $requestedStart = (int)$selected->min();
                // el final del bloque = última hora seleccionada + duración del mismo
                $requestedEnd = (int)$selected->max() + $blockDuration;

                // Si el final solicitado rebasa el fin permitido por la liga
                // para la ventana que contiene el inicio, recortar al máximo permitido
                $containing = $leagueWindows->filter(function ($lw) use ($requestedStart) {
                    return $requestedStart >= (int)$lw->start_minute && $requestedStart < (int)$lw->end_minute;
                });
                if ($containing->isNotEmpty()) {
                    $maxAllowedEnd = (int)$containing->max('end_minute');
                    if ($requestedEnd > $maxAllowedEnd) {
                        $requestedEnd = $maxAllowedEnd;
                    }
                }

                // Validar que la reserva cabe en alguna(s) ventana(s) de liga
                $fitsAny = false;
                foreach ($leagueWindows as $lw) {
                    if ($requestedStart >= (int)$lw->start_minute && $requestedEnd <= (int)$lw->end_minute) {
                        $fitsAny = true;
                        break;
                    }
                }
                if (!$fitsAny) {
                    $label = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'][$dayOfWeek];
                    throw new RuntimeException("El rango solicitado {$this->minuteToHM($requestedStart)}-{$this->minuteToHM($requestedEnd)} para {$field['field_name']} no cabe en las ventanas de liga del día $label.");
                }

                // Validar conflictos con otras reservas de torneos
                $this->validateNoTimeConflict($leagueField->id, $dayOfWeek, $requestedStart, $requestedEnd,
                    $field['field_name']);

                // Insertar la reserva del torneo
                DB::table('tournament_field_reservations')->insert([
                    'tournament_id' => $this->tournament->id,
                    'league_field_id' => $leagueField->id,
                    'day_of_week' => $dayOfWeek,
                    'start_minute' => $requestedStart,
                    'end_minute' => $requestedEnd,
                    'exclusive' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
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

    private function validateNoTimeConflict(int $leagueFieldId, int $dayOfWeek, int $requestedStart, int $requestedEnd, string $fieldName): void
    {
        $conflicts = DB::table('tournament_field_reservations')
            ->where('league_field_id', $leagueFieldId)
            ->where('tournament_id', '!=', $this->tournament->id)
            ->where('day_of_week', $dayOfWeek)
            ->get();

        foreach ($conflicts as $existing) {
            $exStart = (int)$existing->start_minute;
            $exEnd   = (int)$existing->end_minute;

            if (!($requestedEnd <= $exStart || $requestedStart >= $exEnd)) {
                $label = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'][$dayOfWeek];
                throw new RuntimeException(sprintf(
                    'El intervalo %s - %s solicitado para el campo %s se cruza con %s - %s ya reservado por otro torneo en el día %s.',
                    $this->minuteToHM($requestedStart),
                    $this->minuteToHM($requestedEnd),
                    $fieldName,
                    $this->minuteToHM($exStart),
                    $this->minuteToHM($exEnd),
                    $label
                ));
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
