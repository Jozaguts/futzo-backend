<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TournamentCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */

    public function toArray(Request $request): array
    {

        return $this->collection
            ->map(fn($tournament) => new TournamentResource($tournament))->toArray();

    }

    private function positions()
    {
        // todo generar la tabla general de standing table de los equipos en el torneo
        // 1. obtener los equipos del torneo
        // 2. obtener los partidos del torneo
        // 3. obtener los puntos de cada equipo
        // 4. ordenar los equipos por puntos
        // 5. generar la tabla de posiciones

    }
}
