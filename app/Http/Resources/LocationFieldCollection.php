<?php

namespace App\Http\Resources;

use App\Models\TournamentConfiguration;
use App\Models\TournamentField;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class LocationFieldCollection extends ResourceCollection
{
    const dayLabels = [
        'monday' => 'Lunes',
        'tuesday' => 'Martes',
        'wednesday' => 'Miércoles',
        'thursday' => 'Jueves',
        'friday' => 'Viernes',
        'saturday' => 'Sábado',
        'sunday' => 'Domingo',
    ];

    public function toArray(Request $request): array
    {
        $tournamentId = $request->query('tournament_id');
        $config = TournamentConfiguration::where('tournament_id', $tournamentId)->firstOrFail();

        $gameTime = $config->game_time;            // p.ej. 90
        $adminGap = $config->time_between_games;   // p.ej.  0–15
        $buffer = 15;                            // tiempo imprevistos
        $restGlobal = 15;                            // descanso reglamentario

        // cada bloque durará:
        $step = $gameTime + $adminGap + $restGlobal + $buffer; // p.ej. 120

        return $this->collection->map(function ($field, $key) use ($step, $tournamentId) {
            $leagueField = $field->leaguesFields->first();

            return [
                'field_id' => $field->id,
                'step' => $key + 1,
                'field_name' => $field->name,
                'location_id' => $field->location->id,
                'location_name' => $field->location->name,
                'disabled' => false,
                'availability' => $this->transformAvailability(
                    $leagueField->availability,
                    $field->id,
                    $tournamentId,
                    $step
                ),
            ];
        })->toArray();
    }

    /**
     * Transforma la disponibilidad de la liga en bloques de $step minutos,
     * permitiendo arrancar en cada hora marcada.
     */
    private function transformAvailability(array $availability, int $fieldId, int $excludeTournamentId, int $step): array
    {
        // 1) Reservas de otros torneos
        $query = TournamentField::where('field_id', $fieldId);
        if ($excludeTournamentId) {
            $query->where('tournament_id', '!=', $excludeTournamentId);
        }
        $bookings = $query->pluck('availability')->all();

        $bookedSlots = []; // ['monday'=>['09:00','10:00',...], ...]
        $fullDay = []; // ['monday'=>true, ...]

        foreach ($bookings as $json) {
            foreach ($json as $day => $data) {
                if (empty($data['intervals'])) {
                    continue;
                }
                foreach ($data['intervals'] as $int) {
                    if ($int['value'] === '*' && !empty($int['selected'])) {
                        $fullDay[$day] = true;
                    }
                    if ($int['value'] !== '*' && !empty($int['selected'])) {
                        $bookedSlots[$day][] = $int['value'];
                    }
                }
            }
        }

        $result = [];
        $daysOrder = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($daysOrder as $day) {
            if (empty($availability[$day]['enabled'])) {
                continue;
            }

            $startH = (int)$availability[$day]['start']['hours'];
            $startM = (int)$availability[$day]['start']['minutes'];
            $endH = (int)$availability[$day]['end']['hours'];
            $endM = (int)$availability[$day]['end']['minutes'];

            $start = $startH * 60 + $startM;
            $end = $endH * 60 + $endM;

            $intervals = [];
            //  Generamos bloques de $step minutos desde $start hasta <= $end
            for ($t = $start; $t + $step <= $end; $t += $step) {
                $from = sprintf('%02d:%02d', intdiv($t, 60), $t % 60);
                $to = sprintf('%02d:%02d', intdiv($t + $step, 60), ($t + $step) % 60);


                $intervals[] = [
                    'value' => ['start' => $from, 'end' => $to],
                    'text' => "$from – $to",
                    'selected' => false,
                    'disabled' => in_array($from, $bookedSlots[$day] ?? [], true) || !empty($fullDay[$day]),
                ];
            }

            $result[$day] = [
                'enabled' => true,
                'available_range' => sprintf('%02d:%02d a %02d:%02d', $startH, $startM, $endH, $endM),
                'intervals' => $intervals,
                'label' => self::dayLabels[$day],
            ];
            if ($t < $end) {
                $from = sprintf('%02d:%02d', intdiv($t, 60), $t % 60);
                $to = sprintf('%02d:%02d', intdiv($end, 60), $end % 60);
                $intervals[] = [
                    'value' => ['start' => $from, 'end' => $to],
                    'text' => "$from – $to",
                    'selected' => false,
                    'disabled' => true,
                    'is_partial' => true,
                ];
            }
        }

        $result['isCompleted'] = false;
        return $result;
    }
}
