<?php

namespace App\Http\Resources;

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
                'availability' => $this->transformAvailability($leagueField->availability),
            ];
        })->toArray();
    }

    private function transformAvailability(array $availability): array
    {
        $result = [];
        $daysOrder = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($daysOrder as $day) {
            // Ignora si no hay datos o no está habilitado
            if (!isset($availability[$day]) || $day === 'isCompleted' || empty($availability[$day]['enabled'])) {
                continue;
            }

            // Convertir la hora de inicio y fin a minutos
            $startHour = (int)$availability[$day]['start']['hours'];
            $startMinute = (int)$availability[$day]['start']['minutes'];
            $endHour = (int)$availability[$day]['end']['hours'];
            $endMinute = (int)$availability[$day]['end']['minutes'];

            $start = $startHour * 60 + $startMinute;
            $end = $endHour * 60 + $endMinute;
            $step = 60; // Intervalo de 1 hora (60 minutos)

            // Rango total disponible
            $availableRange = sprintf(
                '%02d:%02d a %02d:%02d',
                $startHour, $startMinute,
                $endHour, $endMinute
            );

            $intervals = [['value' => '*', 'text' => 'Todo el dia', 'selected' => true]];
            for ($time = $start; $time + $step <= $end; $time += $step) {
                $hourStart = sprintf('%02d:%02d', intdiv($time, 60), $time % 60);
                $intervals[] = [
                    'value' => $hourStart,
                    'text' => $hourStart,
                    'selected' => false
                ];
            }

            $result[$day] = [
                'enabled' => true,
                'available_range' => $availableRange,
                'intervals' => $intervals,
                'label' => self::dayLabels[$day]
            ];
        }
        $result['isCompleted'] = false;

        return $result;
    }
}
