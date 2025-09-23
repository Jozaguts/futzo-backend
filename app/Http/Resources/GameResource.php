<?php

namespace App\Http\Resources;

use App\Models\Game;
use App\Models\LeagueField;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * Arma intervalos reservados [inicio, fin] para todos los partidos del mismo campo y fecha (de toda la liga, gracias al LeagueScope), incluyendo el propio partido.
     * Al generar las franjas disponibles, descarta cualquier slot que se solape con algún intervalo reservado (no solo si coincide exacto el inicio).
     * Restringe siempre a la disponibilidad del “día de la fecha seleccionada” para evitar devolver el último día del arreglo semanal cuando no se pasa date.
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // 1) Fecha base: selección o fecha de partido
        $selectedDate = $request->has('date')
            ? Carbon::parse($request->input('date'))->startOfDay()
            : $this->resource->match_date->copy()->startOfDay();
        $dayOfWeek = strtolower($selectedDate->format('l'));

        // 2) Campo específico: del request o el original del juego
        $fieldId = (int)$request->input('field_id', $this->resource->field_id);

        // 3) Fase activa y sus límites
        $currentPhase = $this->resource->tournamentPhase;
        $tournamentId = $this->resource->tournament_id;
        $phaseGamesQuery = Game::where('tournament_id', $tournamentId)
            ->where('tournament_phase_id', optional($currentPhase)->id);
        $phaseStart = $phaseGamesQuery->min('match_date');
        $phaseEnd = $phaseGamesQuery->max('match_date');

        // 4) Si la fecha solicitada está fuera de la ventana de la fase, no hay opciones
        if ($request->has('date') && ($selectedDate->lt($phaseStart) || $selectedDate->gt($phaseEnd))) {
            $options = [];
        } else {
            // 5) Duración del partido (game_time + gap + pausas)
            $config = $this->resource->tournament->configuration;
            $matchDuration = ($config->game_time ?? 0) + ($config->time_between_games ?? 0) + 15 + 15; // minutos

            // 6) Ventanas efectivas: field ∩ league − reservas de otros torneos
            $leagueField = LeagueField::where('field_id', $fieldId)
                ->where('league_id', $this->resource->tournament->league_id)
                ->first();
            $availabilityService = app(AvailabilityService::class);
            $weekly = $leagueField
                ? $availabilityService->getWeeklyWindowsForLeagueField($leagueField->id, $this->resource->tournament_id)
                : [];

            // 7) Construir intervalos reservados (cualquier torneo en el mismo campo/fecha)
            $reservedIntervals = [];

            // incluir el propio juego (si tiene hora)
            if (!empty($this->resource->match_time)) {
                $curStart = Carbon::createFromFormat('H:i', $this->resource->match_time);
                $reservedIntervals[] = [
                    $curStart->hour * 60 + $curStart->minute,
                    $curStart->copy()->addMinutes($matchDuration)->hour * 60 + $curStart->copy()->addMinutes($matchDuration)->minute,
                ];
            }

            // otros juegos del mismo campo y fecha (dentro de la misma liga por LeagueScope)
            $otherGames = Game::with(['tournament.configuration'])
                ->where('field_id', $fieldId)
                ->whereDate('match_date', $selectedDate)
                ->where('id', '!=', $this->resource->id)
                ->get(['id', 'match_time', 'tournament_id']);
            foreach ($otherGames as $g) {
                if (empty($g->match_time)) { continue; }
                $gStart = Carbon::createFromFormat('H:i', $g->match_time);
                $gCfg = optional($g->tournament->configuration);
                $gDuration = ($gCfg->game_time ?? $matchDuration)
                    + ($gCfg->time_between_games ?? 0)
                    + 15 + 15;
                // Si sumamos doble 15 al fallback, nos pasamos; aseguremos fallback correcto
                if (!isset($g->tournament->configuration)) {
                    $gDuration = $matchDuration; // usa duración del torneo actual si no hay config cargada
                }
                $startMin = $gStart->hour * 60 + $gStart->minute;
                $endMin = $startMin + $gDuration;
                $reservedIntervals[] = [$startMin, $endMin];
            }

            // 8) Generar opciones de franjas evitando solapes con reservados
            $intervals = [];
            $map = [0 => 'sunday', 1 => 'monday', 2 => 'tuesday', 3 => 'wednesday', 4 => 'thursday', 5 => 'friday', 6 => 'saturday'];
            foreach ($weekly as $dow => $ranges) {
                $day = $map[$dow];
                if ($day !== $dayOfWeek) {
                    continue;
                }
                $slotDate = $selectedDate;
                $freeSlots = [];
                foreach ($ranges as [$s, $e]) {
                    $start = $slotDate->copy()->setTime(intdiv($s, 60), $s % 60);
                    $end = $slotDate->copy()->setTime(intdiv($e, 60), $e % 60);
                    $cursor = $start->copy();
                    while ($cursor->copy()->addMinutes($matchDuration)->lessThanOrEqualTo($end)) {
                        $slotStart = $cursor->copy();
                        $slotEnd = $cursor->copy()->addMinutes($matchDuration);
                        $slotStartMin = $slotStart->hour * 60 + $slotStart->minute;
                        $slotEndMin = $slotEnd->hour * 60 + $slotEnd->minute;

                        // verificar solape con cualquier reservado
                        $overlaps = false;
                        foreach ($reservedIntervals as [$rs, $re]) {
                            if ($slotStartMin < $re && $slotEndMin > $rs) { // solapan
                                $overlaps = true; break;
                            }
                        }
                        if (!$overlaps) {
                            $freeSlots[] = [
                                'start' => $slotStart->format('H:i'),
                                'end' => $slotEnd->format('H:i'),
                            ];
                        }
                        $cursor->addMinutes($matchDuration);
                    }
                }
                if (!empty($freeSlots)) {
                    $intervals['day'] = $day;
                    $intervals['hours'] = $freeSlots;
                }
            }
            $options = [
                ['field_id' => (int)$fieldId, 'available_intervals' => $intervals]
            ];
        }
        $homeGroupKey = $this->resource->getAttribute('home_group_key');
        $awayGroupKey = $this->resource->getAttribute('away_group_key');
        $storedGroupKey = $this->resource->getAttribute('group_key');
        $groupSummary = $this->resource->getAttribute('group_summary');

        $resolvedGroupKey = $storedGroupKey;
        if (!$resolvedGroupKey && $homeGroupKey && $homeGroupKey === $awayGroupKey) {
            $resolvedGroupKey = $homeGroupKey;
        }

        $groupPayload = null;
        if ($resolvedGroupKey) {
            $groupPayload = [
                'key' => $resolvedGroupKey,
                'name' => data_get($groupSummary, 'name', "Grupo {$resolvedGroupKey}"),
                'teams_count' => data_get($groupSummary, 'teams_count'),
                'teams' => data_get($groupSummary, 'teams', []),
            ];

            if ($groupPayload['teams_count'] === null) {
                $groupPayload['teams_count'] = count($groupPayload['teams']);
            }
        }

        return [
            'id' => $this->resource->id,
            'home' => [
                'id' => $this->resource->homeTeam->id,
                'name' => $this->resource->homeTeam->name,
                'image' => $this->resource->homeTeam->image,
                'goals' => $this->resource->home_goals,
                'group_key' => $this->when(!is_null($homeGroupKey) || $resolvedGroupKey, $homeGroupKey ?? $resolvedGroupKey),
            ],
            'away' => [
                'id' => $this->resource->awayTeam->id,
                'name' => $this->resource->awayTeam->name,
                'image' => $this->resource->awayTeam->image,
                'goals' => $this->resource->away_goals,
                'group_key' => $this->when(!is_null($awayGroupKey) || $resolvedGroupKey, $awayGroupKey ?? $resolvedGroupKey),
            ],
            'details' => [
                'date' => $this->resource->match_date->translatedFormat('D j/n'),
                'raw_date' => $this->resource->match_date->toDateTimeString(),
                'raw_time' => $this->resource->match_time,
                'time' => $this->resource->match_time ? [
                    'hours' => Carbon::createFromFormat('H:i', $this->resource->match_time)->hour,
                    'minutes' => Carbon::createFromFormat('H:i', $this->resource->match_time)->minute,
                ] : null,
                'field' => [
                    'id' => $this->resource->field_id,
                    'name' => optional($this->resource->field)->name ?: 'Campo desconocido',
                ],
                'location' => [
                    'id' => $this->resource->location_id,
                    'name' => optional($this->resource->location)->name ?: 'Ubicación desconocida',
                ],
                'referee' => optional($this->resource->referee)->name ?: 'Por asignar',
                'day_of_week' => $dayOfWeek,
                'tournament' => $this->resource->tournament->name,
            ],
            'round' => $this->resource->round,
            'status' => $this->resource->status,
            'result' => $this->resource->result,
            'start_date' => $this->resource->tournament->start_date,
            'end_date' => $this->resource->tournament->games()->orderBy('match_date', 'desc')->first()->match_date,
            'options' => $options,
            'phase' => [
                'id' => optional($this->resource->tournamentPhase)->id,
                'name' => optional($this->resource->tournamentPhase?->phase)->name,
            ],
            'group_key' => $this->when($resolvedGroupKey, $resolvedGroupKey),
            'group' => $this->when(!empty($groupPayload), $groupPayload),
        ];
    }
}
