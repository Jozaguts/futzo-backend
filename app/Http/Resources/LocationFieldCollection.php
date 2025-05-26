<?php

namespace App\Http\Resources;

use App\Models\Tournament;
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
        $tournament = Tournament::findOrFail($tournamentId);
        if ($tournament->league_id !== auth()->user()->league_id) {
            abort(403, 'El torneo no pertenece a tu liga');
        }
        $config = TournamentConfiguration::where('tournament_id', $tournamentId)->firstOrFail();

        $gameTime = $config->game_time;            // p.ej. 90
        $adminGap = $config->time_between_games;   // p.ej.  0–15
        $buffer = 15;                            // tiempo imprevistos
        $restGlobal = 15;                            // descanso reglamentario

        // cada bloque durará:
        $step = $gameTime + $adminGap + $restGlobal + $buffer; // p.ej. 120

        return $this->collection->map(function ($field, $key) use ($tournament, $step) {
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
                // PASAMOS slot de 60 minutos
                'availability' => $this->transformAvailability(
                    $leagueField->availability,
                    $field->id,
                    $tournament->id,
                    60,  // **1 hora**
                    $tournament->league_id,
                ),
            ];
        })->toArray();
    }

    /**
     * Transforma la disponibilidad de la liga en bloques de $step minutos,
     * permitiendo arrancar en cada hora marcada.
     */
    private function transformAvailability(array $leagueFieldGlobalAvailability, int $fieldId, int $excludeTournamentId, int $step, int $leagueId): array
    {
        $bookings = TournamentField::where('field_id', $fieldId)
            ->whereHas('tournament', function ($q) use ($leagueId, $excludeTournamentId) {
                $q->where('league_id', $leagueId);
                if ($excludeTournamentId) {
                    $q->where('id', '!=', $excludeTournamentId);
                }
            })
            ->pluck('availability')
            ->all();

        $bookedSlots = []; // ej. ['monday'=>['09:00','11:00', ...], ...]

        foreach ($bookings as $json) {
            foreach ($json as $day => $data) {
                foreach ($data['intervals'] ?? [] as $int) {
                    if (!empty($int['selected']) && $int['selected'] === true && is_string($int['value'])) {
                        $bookedSlots[$day][] = $int['value'];
                    }
                }
            }
        }

        // 2) Generamos slots de 1 hora dentro de cada día habilitado
        $result = [];
        $daysOrder = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($daysOrder as $day) {
            if (empty($leagueFieldGlobalAvailability[$day]['enabled'])) {
                continue;
            }

            $startH = (int)$leagueFieldGlobalAvailability[$day]['start']['hours'];
            $startM = (int)$leagueFieldGlobalAvailability[$day]['start']['minutes'];
            $endH = (int)$leagueFieldGlobalAvailability[$day]['end']['hours'];
            $endM = (int)$leagueFieldGlobalAvailability[$day]['end']['minutes'];

            $start = $startH * 60 + $startM;
            $end = $endH * 60 + $endM;

            $intervals = [];
            // Generar bloques completos de $step
            for ($t = $start; $t + $step <= $end; $t += $step) {
                $fromText = sprintf('%02d:%02d', intdiv($t, 60), $t % 60);

                $intervals[] = [
                    // <-- aquí sólo la hora de inicio como value
                    'value' => $fromText,
                    'text' => $fromText,
                    'selected' => false,
                    'disabled' => in_array($fromText, $bookedSlots[$day] ?? [], true),
                ];
            }

            // Si queda un bloque parcial al final...
            if (isset($t) && $t < $end) {
                $fromText = sprintf('%02d:%02d', intdiv($t, 60), $t % 60);

                $intervals[] = [
                    'value' => $fromText,
                    'text' => $fromText,
                    'selected' => false,
                    'disabled' => true,
                    'is_partial' => true,
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
