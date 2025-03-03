<?php

namespace App\Services;

use App\Models\Tournament;
use Illuminate\Support\Collection;

class ScheduleGeneratorService
{

    public function generateFor(Tournament $tournament): void
    {
        $schedule = $this->makeSchedule($tournament->teams);
    }

    private function makeSchedule(Collection $teams): Collection
    {
        $teamsCount = $teams->count();

        // Si el número de equipos es impar, agrega un equipo "fantasma" para hacerlo par
        if ($teamsCount % 2 != 0) {
            $teams->push(null);
            $teamsCount = $teams->count(); // Actualiza el conteo de equipos
        }

        $half = intdiv($teamsCount, 2);
        $rounds = $teamsCount - 1; // NumJornadas
        $matches = collect();

        for ($j = 0; $j < $rounds; $j++) {
            $round = collect();
            for ($i = 0; $i < $half; $i++) {
                $home = $teams->get($i);
                $away = $teams->get($teamsCount - 1 - $i);
                if ($away !== null) { // Evitar incluir el equipo fantasma en los partidos
                    $round->push([
                        'home' => $home,
                        'away' => $away,
                    ]);
                }
            }
            $matches->push($round);

            // Rotar equipos, excepto el primero
            if ($teamsCount > 2) { // Solo rotar si hay más de un equipo aparte del fijo
                $teams = collect([$teams->first()])
                    ->merge($teams->slice(2)->push($teams->get(1))); // Correctamente rotar los equipos
            }
        }

        return $matches;
    }

}
