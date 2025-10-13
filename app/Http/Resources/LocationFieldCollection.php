<?php

namespace App\Http\Resources;

use App\Models\Tournament;
use App\Models\TournamentConfiguration;
use App\Services\AvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class LocationFieldCollection extends ResourceCollection
{
    const array dayLabels = [
        'monday' => 'Lunes',
        'tuesday' => 'Martes',
        'wednesday' => 'Miércoles',
        'thursday' => 'Jueves',
        'friday' => 'Viernes',
        'saturday' => 'Sábado',
        'sunday' => 'Domingo',
    ];

    /*
     * devuelve disponibilidad “usable” por campo para un torneo (ya resta reservas exclusivas de otros torneos).
     * */
    public function toArray(Request $request): array
    {
        $tournamentId = $request->query('tournament_id');
        $tournament = Tournament::findOrFail($tournamentId);
        if ($tournament->league_id !== auth()->user()->league_id) {
            abort(403, 'El torneo no pertenece a tu liga');
        }
        $config = TournamentConfiguration::where('tournament_id', $tournamentId)->firstOrFail();

        $gameTime = $config->game_time;            // ej. 90
        $adminGap = $config->time_between_games;   // ej.  0–15
        // UI: anclar selección a horas; slots reales = gameTime+gap se validan más adelante
        $uiStep = 60;

        return $this->collection->map(function ($field, $key) use ($tournament, $uiStep) {
            $leagueField = $field
                ->leaguesFields()
                ->where('league_id', $tournament->league_id)
                ->first();

            return [
                'field_id' => $field->id,
                'step' => $key + 1,
                'field_name' => $field->name,
                'location_id' => $field->location->id,
                'location_name' => $field->location->name,
                'disabled' => false,
                // Disponibilidad derivada de field/league windows menos reservas de otros torneos
                'availability' => $this->buildAvailabilityFromWindows(
                    $leagueField->id,
                    $tournament->id,
                    $uiStep
                ),
            ];
        })->toArray();
    }

    private function buildAvailabilityFromWindows(int $leagueFieldId, int $excludeTournamentId, int $step): array
    {
        $service = app(AvailabilityService::class);
        $weekly = $service->getWeeklyWindowsForLeagueField($leagueFieldId, $excludeTournamentId);

        $map = [
            1 => 'monday', 2 => 'tuesday', 3 => 'wednesday', 4 => 'thursday', 5 => 'friday', 6 => 'saturday', 0 => 'sunday',
        ];
        $labels = self::dayLabels;
        $out = [];
        foreach ($weekly as $dow => $ranges) {
            $dayKey = $map[$dow];
            $intervals = [];
            foreach ($ranges as [$s, $e]) {
                for ($t = $s; $t + $step <= $e; $t += $step) {
                    $fromText = sprintf('%02d:%02d', intdiv($t, 60), $t % 60);
                    $intervals[] = [
                        'value' => $fromText,
                        'text' => $fromText,
                        'selected' => false,
                        'disabled' => false,
                    ];
                }
                if ($t ?? null) {
                    if ($t < $e) {
                        $fromText = sprintf('%02d:%02d', intdiv($t, 60), $t % 60);
                        $intervals[] = [
                            'value' => $fromText,
                            'text' => $fromText,
                            'selected' => false,
                            'disabled' => true,
                            'is_partial' => true,
                        ];
                    }
                }
            }
            // available_range usando primer inicio y último fin del día
            if (empty($ranges)) {
                $out[$dayKey] = [
                    'enabled' => false,
                    'available_range' => null,
                    'intervals' => [],
                    'label' => $labels[$dayKey],
                    'mobile_label' => $labels[$dayKey][0],
                ];
                continue;
            }

            $firstStart = $ranges[0][0];
            $lastEnd = $ranges[count($ranges)-1][1];

            if (empty($intervals)) {
                $out[$dayKey] = [
                    'enabled' => false,
                    'available_range' => sprintf('%02d:%02d a %02d:%02d', intdiv($firstStart,60), $firstStart%60, intdiv($lastEnd,60), $lastEnd%60),
                    'intervals' => [],
                    'label' => $labels[$dayKey],
                    'mobile_label' => $labels[$dayKey][0],
                ];
                continue;
            }

            $out[$dayKey] = [
                'enabled' => true,
                'available_range' => sprintf('%02d:%02d a %02d:%02d', intdiv($firstStart,60), $firstStart%60, intdiv($lastEnd,60), $lastEnd%60),
                'intervals' => $intervals,
                'label' => $labels[$dayKey],
                'mobile_label' => $labels[$dayKey][0],
            ];
        }
//        $out['isCompleted'] = false;
        return $out;
    }
}
