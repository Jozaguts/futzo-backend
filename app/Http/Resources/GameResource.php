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
        // 1) Fecha base para generar los slots:
        //    si viene ?date= usamos esa fecha; si no, la del partido
        $selectedDate = $request->has('date')
            ? Carbon::parse($request->input('date'))->startOfDay()
            : $this->match_date->copy()->startOfDay();
        $dayOfWeek = strtolower($selectedDate->format('l'));
        $config = $this->tournament->configuration;
        $matchDuration = 60; // bloques de una hora
        $fieldIds = $this->tournament
            ->fields()    // relación Tournament::fields() que apunta a tournament_fields
            ->pluck('field_id')
            ->toArray();
        $leagueAvail = LeagueField::whereIn('field_id', $fieldIds)
            ->get()
            ->keyBy('field_id')
            ->map->availability
            ->toArray();
        // 5) Reservas definidas en tournament_fields (JSON)
        $tournReserved = TournamentField::where('tournament_id', $this->tournament->id)
            ->get()
            ->pluck('availability', 'field_id')
            ->toArray();
        // 6) Partidos ya agendados para la fecha seleccionada
        $gamesReserved = Game::where('tournament_id', $this->tournament->id)
            ->whereDate('match_date', $selectedDate)
            ->get()
            ->groupBy('field_id')
            ->map(fn($games) => $games
                ->pluck('match_time')
                ->map(fn($t) => substr($t, 0, 5))
                ->toArray()
            )
            ->toArray();

        $options = [];
        foreach ($leagueAvail as $fieldId => $daysConfig) {
            $fieldOptions = [];
            foreach ($daysConfig as $day => $cfg) {
                if (!($cfg['enabled'] ?? false)) {
                    continue;
                }
                if ($request->has('date') && $day !== $dayOfWeek) {
                    continue;
                }
                // 8) Determino la fecha concreta para ese día
                if ($request->has('date')) {
                    $slotDate = $selectedDate;
                } else {
                    // al cargar inicialmente, hallar día en la misma semana del match_date
                    $weekStart = $this->match_date
                        ->copy()
                        ->startOfWeek($this->match_date->dayOfWeek);
                    // usar DateTime modify para mover al día correcto
                    $slotDate = $weekStart->copy()->modify($day);
                }
                // 4) Generar todos los posibles slots dentro del rango global
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
                // 10) Slots reservados en tournament_fields.availability
                $takenByConfig = [];
                if (isset($tournReserved[$fieldId][$day]['intervals'])) {
                    $takenByConfig = collect($tournReserved[$fieldId][$day]['intervals'])
                        ->filter(fn($i) => !empty($i['selected']))
                        ->pluck('value')
                        ->toArray();
                }

                // 11) Slots tomados por juegos ya agendados
                $takenByGames = $gamesReserved[$fieldId] ?? [];

                // 12) Calcular slots libres
                $freeSlots = array_values(array_diff($allSlots, $takenByConfig, $takenByGames));

                if (!empty($freeSlots)) {
                    $fieldOptions[$day] = $freeSlots;
                }

            }
            if (!empty($fieldOptions)) {
                $options[] = [
                    'field_id' => $fieldId,
                    'available_intervals' => $fieldOptions,
                ];
            }
        }
        return [
            'id' => $this->id,
            'home' => [
                'id' => $this->homeTeam->id,
                'name' => $this->homeTeam->name,
                'image' => sprintf(
                    'https://ui-avatars.com/api/?name=%s&background=9155FD&color=fff',
                    urlencode($this->homeTeam->name)
                ),
                'goals' => $this->home_goals,
            ],
            'away' => [
                'id' => $this->awayTeam->id,
                'name' => $this->awayTeam->name,
                'image' => sprintf(
                    'https://ui-avatars.com/api/?name=%s&background=8A8D93&color=fff',
                    urlencode($this->awayTeam->name)
                ),
                'goals' => $this->away_goals,
            ],
            'details' => [
                'date' => optional($this->match_date)->translatedFormat('D j/n'),
                'raw_date' => optional($this->match_date)->toDateTimeString(),
                'raw_time' => $this->match_time,
                'time' => $this->match_time ? [
                    'hours' => Carbon::createFromFormat('H:i', $this->match_time)->hour,
                    'minutes' => Carbon::createFromFormat('H:i', $this->match_time)->minute,
                ] : null,
                'field' => [
                    'id' => $this->field_id,
                    'name' => optional($this->field)->name ?: 'Campo desconocido',
                ],
                'location' => [
                    'id' => $this->location_id,
                    'name' => optional($this->location)->name ?: 'Ubicación desconocida',
                ],
                'referee' => optional($this->referee)->name ?: 'Por asignar',
            ],
            'status' => $this->status,
            'result' => $this->result,
            'start_date' => $this->tournament->start_date,
            'options' => $options,
        ];
    }
}
