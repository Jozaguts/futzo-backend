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

        return $this->collection->map(function ($field, $key) use ($tournamentId) {
            $leagueField = $field->leaguesFields->first();

            return [
                'field_id' => $field->id,
                'step' => $key + 1,
                'field_name' => $field->name,
                'location_id' => $field->location->id,
                'location_name' => $field->location->name,
                'disabled' => false,
                // PASAMOS slot de 60 minutos
                'availability' => $this->transformAvailability(
                    $leagueField->availability,
                    $field->id,
                    $tournamentId,
                    60  // **1 hora**
                ),
            ];
        })->toArray();
    }

    /**
     * Transforma la disponibilidad de la liga en bloques de $step minutos,
     * permitiendo arrancar en cada hora marcada.
     */
    private function transformAvailability(array $availability, int $fieldId, int $excludeTournamentId, int $slotDuration): array
    {
        // 1) Sacamos reservas de otros torneos
        $query = TournamentField::where('field_id', $fieldId);
        if ($excludeTournamentId) {
            $query->where('tournament_id', '!=', $excludeTournamentId);
        }
        $bookings = $query->pluck('availability')->all();

        $bookedSlots = []; // ej. ['monday'=>['09:00','11:00', ...], ...]

        foreach ($bookings as $json) {
            foreach ($json as $day => $data) {
                if (empty($data['intervals'])) {
                    continue;
                }
                foreach ($data['intervals'] as $int) {
                    // si está marcado y es un slot concreto (ya no hay '*')
                    if (!empty($int['selected']) && is_array($int['value'])) {
                        // value: ['start'=>'09:00','end'=>'11:00']
                        $bookedSlots[$day][] = $int['value']['start'];
                    }
                }
            }
        }

        // 2) Generamos slots de 1 hora dentro de cada día habilitado
        $result = [];
        $daysOrder = array_keys(self::dayLabels);

        foreach ($daysOrder as $day) {
            if (empty($availability[$day]['enabled'])) {
                continue;
            }

            // convertir límites a minutos desde medianoche
            $sh = (int)$availability[$day]['start']['hours'];
            $sm = (int)$availability[$day]['start']['minutes'];
            $eh = (int)$availability[$day]['end']['hours'];
            $em = (int)$availability[$day]['end']['minutes'];

            $start = $sh * 60 + $sm;
            $end = $eh * 60 + $em;

            $intervals = [];
            // crear bloques de slotDuration (60) minutos
            for ($t = $start; $t + $slotDuration <= $end; $t += $slotDuration) {
                $from = sprintf('%02d:%02d', intdiv($t, 60), $t % 60);
                $to = sprintf('%02d:%02d', intdiv($t + $slotDuration, 60), ($t + $slotDuration) % 60);

                $intervals[] = [
                    'value' => ['start' => $from, 'end' => $to],
                    'text' => "$from – $to",
                    'selected' => false,
                    'disabled' => in_array($from, $bookedSlots[$day] ?? [], true),
                ];
            }

            // si sobra un fragmento parcial al final, lo mostramos deshabilitado
            if (isset($t) && $t < $end) {
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

            $result[$day] = [
                'enabled' => true,
                'available_range' => sprintf('%02d:%02d a %02d:%02d', $sh, $sm, $eh, $em),
                'intervals' => $intervals,
                'label' => self::dayLabels[$day],
            ];
        }

        $result['isCompleted'] = false;
        return $result;
    }
}
