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
            : $this->match_date->copy()->startOfDay();
        $dayOfWeek = strtolower($selectedDate->format('l'));
        // 2) Fase activa y sus límites
        $currentPhase = $this->tournamentPhase;
        $phaseGames = Game::where('tournament_id', $this->tournament_id)
            ->where('tournament_phase_id', $currentPhase->id);
        $phaseStart = $phaseGames->min('match_date');
        $phaseEnd = $phaseGames->max('match_date');
        if ($request->has('date') && ($selectedDate->lt($phaseStart) || $selectedDate->gt($phaseEnd))) {
            $options = [];
        } else {
            // 3) Duración del partido (game_time + gap)
            $config = $this->tournament->configuration;
            $matchDuration = $config->game_time + $config->time_between_games + 15 + 15;

            // 4) Obtener IDs de canchas del torneo
            $fieldIds = $this->tournament->fields()->pluck('field_id')->toArray();

            // 5) Disponibilidad global de liga
            $leagueAvail = LeagueField::whereIn('field_id', $fieldIds)
                ->get()->keyBy('field_id')->map->availability->toArray();

            // 6) Bloqueos en tournament_fields
            $tournReserved = TournamentField::where('tournament_id', $this->tournament_id)
                ->pluck('availability', 'field_id')->toArray();

            // 7) Partidos ya agendados en la fase para la fecha
            $gamesReserved = $phaseGames
                ->whereDate('match_date', $selectedDate)
                ->get()
                ->groupBy('field_id')
                ->map(fn($g) => $g->pluck('match_time')->map(fn($t) => substr($t, 0, 5))->toArray())
                ->toArray();

            // 8) Generar opciones de franjas
            $options = [];
            foreach ($leagueAvail as $fieldId => $daysConfig) {
                $fieldOptions = [];
                foreach ($daysConfig as $day => $cfg) {
                    if (empty($cfg['enabled'])) {
                        continue;
                    }
                    if ($request->has('date') && $day !== $dayOfWeek) {
                        continue;
                    }

                    // Fecha concreta a iterar (dentro de faseStart/phaseEnd)
                    $slotDate = $request->has('date')
                        ? $selectedDate
                        : Carbon::parse($phaseStart)->startOfWeek()->modify($day);

                    // Generar slots entre start y end
                    $start = $slotDate->copy()->setTime((int)$cfg['start']['hours'], (int)$cfg['start']['minutes']);
                    $end = $slotDate->copy()->setTime((int)$cfg['end']['hours'], (int)$cfg['end']['minutes']);
                    $allSlots = [];
                    $cursor = $start->copy();
                    while ($cursor->copy()->addMinutes($matchDuration)->lessThanOrEqualTo($end)) {
                        $allSlots[] = $cursor->format('H:i');
                        $cursor->addMinutes($matchDuration);
                    }

                    // Restar bloques reservados y juegos existentes
                    $takenByConfig = [];
                    if (isset($tournReserved[$fieldId][$day]['intervals'])) {
                        $takenByConfig = collect($tournReserved[$fieldId][$day]['intervals'])
                            ->filter(fn($i) => !empty($i['selected']))->pluck('value')->toArray();
                    }
                    $takenByGames = $gamesReserved[$fieldId] ?? [];

                    $freeSlots = array_values(array_diff($allSlots, $takenByConfig, $takenByGames));
                    if (!empty($freeSlots)) {
                        $fieldOptions[$day] = $freeSlots;
                    }
                }
                if ($fieldOptions) {
                    $options[] = ['field_id' => $fieldId, 'available_intervals' => $fieldOptions];
                }
            }
        }
        // 9) Estructura de respuesta
        return [
            'id' => $this->id,
            'home' => ['id' => $this->homeTeam->id, 'name' => $this->homeTeam->name, 'image' => /* ... */ '', 'goals' => $this->home_goals],
            'away' => ['id' => $this->awayTeam->id, 'name' => $this->awayTeam->name, 'image' => /* ... */ '', 'goals' => $this->away_goals],
            'details' => [ /* ... */],
            'status' => $this->status,
            'result' => $this->result,
            'start_date' => $this->tournament->start_date,
            'options' => $options,
        ];
    }
}
