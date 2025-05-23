<?php

namespace App\Http\Resources;

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
        return $this->collection->map(function ($field, $key) {
            $leagueField = $field->leaguesFields->first();
            return [
                'field_id' => $field->id,
                'step' => ++$key,
                'field_name' => $field->name,
                'location_name' => $field->location->name,
                'location_id' => $field->location->id,
                'disabled' => false,
                'availability' => $this->transformAvailability($leagueField->availability, $field->id),
            ];
        })->toArray();
    }

    private function transformAvailability(array $availability, int $fieldId, ?int $excludeTournamentId = null): array
    {
        // 1) Traer todas las reservas de tournament_fields
        $query = TournamentField::where('field_id', $fieldId);
        if ($excludeTournamentId) {
            $query->where('tournament_id', '!=', $excludeTournamentId);
        }
        $bookings = $query->pluck('availability')->all();

        // 2) Construir dos mapas:
        //    a) $bookedSlots[day] = [ '09:00', '10:00', ... ]
        //    b) $fullDay[day] = true  si existe reserva “Todo el día”
        $bookedSlots = [];
        $fullDay = [];

        foreach ($bookings as $json) {
            foreach ($json as $day => $data) {
                if (!isset($data['intervals'])) {
                    continue;
                }
                foreach ($data['intervals'] as $interval) {
                    if ($interval['value'] === '*' && !empty($interval['selected'])) {
                        // marca todo el día como reservado
                        $fullDay[$day] = true;
                    }
                    // si hay un slot seleccionado distinto de '*', agrégalo
                    if ($interval['value'] !== '*' && !empty($interval['selected'])) {
                        $bookedSlots[$day][] = $interval['value'];
                    }
                }
            }
        }

        // 3) Generar respuesta marcando disabled según $
        $result = [];
        $daysOrder = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($daysOrder as $day) {
            if (!isset($availability[$day]) || empty($availability[$day]['enabled'])) {
                continue;
            }

            $startH = (int)$availability[$day]['start']['hours'];
            $startM = (int)$availability[$day]['start']['minutes'];
            $endH = (int)$availability[$day]['end']['hours'];
            $endM = (int)$availability[$day]['end']['minutes'];

            $start = $startH * 60 + $startM;
            $end = $endH * 60 + $endM;
            $step = 60;

            $intervals = [
                [
                    'value' => '*',
                    'text' => 'Todo el día',
                    'selected' => false,
                    // si fullDay marca este día, deshabilita también “Todo el día”
                    'disabled' => !empty($fullDay[$day]),
                ],
            ];

            for ($t = $start; $t + $step <= $end; $t += $step) {
                $slot = sprintf('%02d:%02d', intdiv($t, 60), $t % 60);
                $intervals[] = [
                    'value' => $slot,
                    'text' => $slot,
                    'selected' => false,
                    // deshabilita si:
                    //  - ya está reservado puntualmente ($bookedSlots)
                    //  - **o** si fullDay está activo
                    'disabled' => !empty($fullDay[$day]) || in_array($slot, $bookedSlots[$day] ?? [], true),
                ];
            }

            $result[$day] = [
                'enabled' => true,
                'available_range' => sprintf('%02d:%02d a %02d:%02d', $startH, $startM, $endH, $endM),
                'intervals' => $intervals,
                'label' => self::dayLabels[$day],
            ];
        }

        $result['isCompleted'] = false;
        return $result;
    }
}
