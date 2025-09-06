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
     *
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
        $fieldId = $request->input('field_id', $this->resource->field_id);
        // 3) Fase activa y sus límites
        $currentPhase = $this->resource->tournamentPhase;
        $tournamentId = $this->resource->tournament_id;
        $phaseGames = Game::where('tournament_id', $tournamentId)
            ->where('tournament_phase_id', $currentPhase->id);
        $phaseStart = $phaseGames->min('match_date');
        $phaseEnd = $phaseGames->max('match_date');
        // 4) Si la fecha solicitada está fuera de la ventana de la fase, no hay opciones
        if ($request->has('date') && ($selectedDate->lt($phaseStart) || $selectedDate->gt($phaseEnd))) {
            $options = [];
        } else {
            // 3) Duración del partido (game_time + gap)
            $config = $this->resource->tournament->configuration;
            $matchDuration = $config->game_time + $config->time_between_games + 15 + 15;

            // 6) Ventanas efectivas: field ∩ league − reservas de otros torneos
            $leagueField = LeagueField::where('field_id', $fieldId)
                ->where('league_id', $this->resource->tournament->league_id)
                ->first();
            $availabilityService = app(AvailabilityService::class);
            $weekly = $leagueField
                ? $availabilityService->getWeeklyWindowsForLeagueField($leagueField->id, $this->resource->tournament_id)
                : [];

            // 8) Partidos ya agendados en la fase para la fecha y campo
            $gamesReserved = $phaseGames
                ->where('field_id', $fieldId)
                ->whereDate('match_date', $selectedDate)
                ->pluck('match_time')
                ->map(fn($t) => substr($t, 0, 5))
                ->toArray();

            // 8) Generar opciones de franjas
            $intervals = [];
            // map dow to day string
            $map = [0=>'sunday',1=>'monday',2=>'tuesday',3=>'wednesday',4=>'thursday',5=>'friday',6=>'saturday'];
            foreach ($weekly as $dow => $ranges) {
                $day = $map[$dow];
                if ($request->has('date') && $day !== $dayOfWeek) {
                    continue;
                }
                $slotDate = $request->has('date') ? $selectedDate : Carbon::parse($phaseStart)->startOfWeek()->next($day);
                $freeSlots = [];
                foreach ($ranges as [$s,$e]) {
                    $start = $slotDate->copy()->setTime(intdiv($s,60), $s%60);
                    $end = $slotDate->copy()->setTime(intdiv($e,60), $e%60);
                    $cursor = $start->copy();
                    while ($cursor->copy()->addMinutes($matchDuration)->lessThanOrEqualTo($end)) {
                        $startStr = $cursor->format('H:i');
                        if (!in_array($startStr, $gamesReserved, true)) {
                            $endStr = $cursor->copy()->addMinutes($matchDuration)->format('H:i');
                            $freeSlots[] = ['start' => $startStr, 'end' => $endStr];
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
                ['field_id' => $fieldId, 'available_intervals' => $intervals]
            ];
        }
        return [
            'id' => $this->resource->id,
            'home' => [
                'id' => $this->resource->homeTeam->id,
                'name' => $this->resource->homeTeam->name,
                'image' => $this->resource->homeTeam->image,
                'goals' => $this->resource->home_goals,
            ],
            'away' => [
                'id' => $this->resource->awayTeam->id,
                'name' => $this->resource->awayTeam->name,
                'image' => $this->resource->awayTeam->image,
                'goals' => $this->resource->away_goals,
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
        ];
    }
}
