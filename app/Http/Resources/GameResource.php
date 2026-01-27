<?php

namespace App\Http\Resources;

use App\Models\Game;
use App\Models\LeagueField;
use App\Services\AvailabilityService;
use App\Support\MatchDuration;
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
        if ($request->has('date')) {
            $selectedDate = Carbon::parse($request->input('date'))->startOfDay();
        } elseif ($this->resource->match_date) {
            $selectedDate = $this->resource->match_date->copy()->startOfDay();
        } elseif ($this->resource->tournament->start_date) {
            $selectedDate = $this->resource->tournament->start_date->copy()->startOfDay();
        } else {
            $selectedDate = Carbon::now()->startOfDay();
        }
        $dayOfWeek = strtolower($selectedDate->format('l'));

        // 2) Campo específico: del request o el original del juego
        $fieldId = (int)$request->input('field_id', $this->resource->field_id);

        // 3) Duración total a reservar (tiempo de juego + gap admin + buffer según modalidad)
        $config = $this->resource->tournament->configuration;
        $matchDuration = MatchDuration::minutes($config);

        // 4) Ventanas efectivas: field ∩ league − reservas de otros torneos
        $leagueField = LeagueField::where('field_id', $fieldId)
            ->where('league_id', $this->resource->tournament->league_id)
            ->first();
        $availabilityService = app(AvailabilityService::class);
        $weekly = $leagueField
            ? $availabilityService->getWeeklyWindowsForLeagueField($leagueField->id, $this->resource->tournament_id)
            : [];

        // 5) Construir intervalos reservados (cualquier torneo en el mismo campo/fecha)
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
            $gCfg = $g->tournament->configuration;
            $gDuration = MatchDuration::minutes($gCfg, $matchDuration);
            $startMin = $gStart->hour * 60 + $gStart->minute;
            $endMin = $startMin + $gDuration;
            $reservedIntervals[] = [$startMin, $endMin];
        }

        // 6) Generar opciones de franjas evitando solapes con reservados
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

        // Cuando el controlador cargó la relación de penales, repartimos los intentos por equipo
        $penaltyRelation = $this->resource->relationLoaded('penalties')
            ? $this->resource->penalties
            : collect();

        $homeShootout = PenaltyResource::collection(
            collect($penaltyRelation)->where('team_id', $this->resource->home_team_id)->values()
        );
        $awayShootout = PenaltyResource::collection(
            collect($penaltyRelation)->where('team_id', $this->resource->away_team_id)->values()
        );

        $lastScheduledGame = $this->resource->tournament
            ->games()
            ->whereNotNull('match_date')
            ->orderBy('match_date', 'desc')
            ->first();

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
                'date' => $this->resource->match_date?->translatedFormat('D j/n'),
                'raw_date' => $this->resource->match_date?->toDateTimeString(),
                'raw_time' => $this->resource->match_time,
                'time' => $this->resource->match_time ? [
                    'hours' => Carbon::parse($this->resource->match_time)->hour,
                    'minutes' => Carbon::parse($this->resource->match_time)->minute,
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
            'slot_status' => $this->resource->slot_status,
            'result' => $this->resource->result,
            'start_date' => $this->resource->tournament->start_date,
            'end_date' => $lastScheduledGame?->match_date,
            'options' => $options,
            'penalties' => [
                'decided' => (bool)$this->resource->decided_by_penalties,
                'winner_team_id' => $this->resource->penalty_winner_team_id,
                'home_goals' => $this->resource->penalty_home_goals,
                'away_goals' => $this->resource->penalty_away_goals,
            ],
            'penalty_shootout' => [
                // La UI consume esta estructura para reconstruir la tanda en el formulario de actas
                'home' => $homeShootout,
                'away' => $awayShootout,
            ],
            'penalty_draw_enabled' => (bool)$this->resource->tournament->penalty_draw_enabled,
            'phase' => [
                'id' => optional($this->resource->tournamentPhase)->id,
                'name' => optional($this->resource->tournamentPhase?->phase)->name,
            ],
            'group_key' => $this->when($resolvedGroupKey, $resolvedGroupKey),
            'group' => $this->when(!empty($groupPayload), $groupPayload),
        ];
    }
}
