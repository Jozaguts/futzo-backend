<?php

namespace App\Http\Resources;

use App\Models\Game;
use App\Models\LeagueField;
use App\Models\TournamentField;
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

            // 6) Disponibilidad global de la liga solo para este campo
            $leagueAvail = LeagueField::where('field_id', $fieldId)
                ->first()
                ->availability ?? [];

            // 7) Bloqueos en tournament_fields solo para este campo
            $tournReserved = TournamentField::where('field_id', $fieldId)
                ->value('availability') ?? [];

            // 8) Partidos ya agendados en la fase para la fecha y campo
            $gamesReserved = $phaseGames
                ->where('field_id', $fieldId)
                ->whereDate('match_date', $selectedDate)
                ->pluck('match_time')
                ->map(fn($t) => substr($t, 0, 5))
                ->toArray();

            // 8) Generar opciones de franjas
            $intervals = [];
            foreach ($leagueAvail as $day => $cfg) {
                if (empty($cfg['enabled'])) {
                    continue;
                }
                if ($request->has('date') && $day !== $dayOfWeek) {
                    continue;
                }

                // Determinar fecha a iterar dentro de fase
                $slotDate = $request->has('date')
                    ? $selectedDate
                    : Carbon::parse($phaseStart)->startOfWeek()->modify($day);

                // Generar todos los posibles inicios de slot
                $start = $slotDate->copy()
                    ->setTime((int)$cfg['start']['hours'], (int)$cfg['start']['minutes']);
                $end = $slotDate->copy()
                    ->setTime((int)$cfg['end']['hours'], (int)$cfg['end']['minutes']);

                $allSlots = [];
                $cursor = $start->copy();
                while ($cursor->copy()->addMinutes($matchDuration)->lessThanOrEqualTo($end)) {
                    $allSlots[] = $cursor->format('H:i');
                    $cursor->addMinutes($matchDuration);
                }

                // Restar bloques reservados
                $takenByConfig = [];
                if (!empty($tournReserved[$day]['intervals'] ?? [])) {
                    $takenByConfig = collect($tournReserved[$day]['intervals'])
                        ->filter(fn($i) => !empty($i['in_use']))
                        ->pluck('value')
                        ->toArray();
                }

                // Restar partidos existentes
                $takenByGames = $gamesReserved;

                // Calcular start/end de cada intervalo libre
                $rawFree = array_values(array_diff($allSlots, $takenByConfig, $takenByGames));
                $freeSlots = [];
                foreach ($rawFree as $startTime) {
                    $endTime = Carbon::createFromFormat('H:i', $startTime)
                        ?->addMinutes($matchDuration)
                        ->format('H:i');
                    $freeSlots[] = ['start' => $startTime, 'end' => $endTime];
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
                'image' => sprintf(
                    'https://ui-avatars.com/api/?name=%s&background='.  str_replace('#','', $this->resource->homeTeam->colors['home']['primary']) ?: 'AFA'.'&color=fff',
                    urlencode($this->resource->homeTeam->name)
                ),
                'goals' => $this->resource->home_goals,
            ],
            'away' => [
                'id' => $this->resource->awayTeam->id,
                'name' => $this->resource->awayTeam->name,
                'image' => sprintf(
                    'https://ui-avatars.com/api/?name=%s&background='. str_replace('#','',$this->resource->awayTeam->colors['away']['primary']) ?: 'AFA' .'&color=fff',
                    urlencode($this->resource->awayTeam->name)
                ),
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
