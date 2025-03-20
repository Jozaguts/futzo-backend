<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TournamentFieldPivot extends Pivot
{
    protected $table = 'tournament_fields';

    protected function availability(): Attribute
    {
        return Attribute::make(
            get: fn($value) => json_decode($value, true),
            set: fn($value) => json_encode($this->transformToIntervals($value))
        );
    }

    public function setAvailabilityAttribute($value): void
    {
        $transformed = $this->transformToIntervals($value);
        $this->attributes['availability'] = json_encode($transformed);
    }

    private function transformToIntervals(array $availability): array
    {
        $result = [];

        foreach ($availability as $day => $data) {
            if ($day === 'isCompleted') {
                continue;
            }

            $enabled = $data['enabled'] ?? false;

            if (!$enabled) {
                $result[$day] = [
                    'enabled' => false,
                    'intervals' => []
                ];
                continue;
            }

            $start = ((int)$data['start']['hours']) * 60 + (int)$data['start']['minutes'];
            $end = ((int)$data['end']['hours']) * 60 + (int)$data['end']['minutes'];
            $step = 120; // 2 horas

            $intervals = [];
            for ($time = $start; $time + $step <= $end; $time += $step) {
                $intervals[] = [
                    'start' => $this->formatTime($time),
                    'end' => $this->formatTime($time + $step),
                    'selected' => false
                ];
            }

            $result[$day] = [
                'enabled' => true,
                'intervals' => $intervals
            ];
        }

        $result['isCompleted'] = false;
        return $result;
    }

    private function formatTime($totalMinutes): string
    {
        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;
        return sprintf('%02d:%02d', $hours, $minutes);
    }

}
