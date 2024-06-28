<?php

namespace App\Http\Resources;

use App\Models\Category;
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
        $data = [];
        $data['categories'] = Category::select('id', 'name','age_range')->get()->toArray();
        logger($this->collection);
        $data['tournaments'] =  $this->collection->map(fn ($tournament) => [

            'id' => $tournament->id,
            'category' => [
                'id' => $tournament->category->id,
                'name' => $tournament->category->name,
            ],
            'format' => [
                'id' => $tournament->format->id,
                'name' => $tournament->format->name,
            ],
            'start_date' => $tournament->start_date,
            'end_date' => $tournament->end_date,
            'status' => $tournament->status,
            'name' => $tournament->name,
            'slug' => str($tournament->name)->slug('-'),
            'teams' => $tournament->teams_count,
            'players' => $tournament->players_count,
            'matches' => $tournament->games_count,
            'league' => $tournament->league->name,
        ])->toArray();

        return $data;
    }

    private function positions()
    {
       // todo generar la tabla general de posines de los equipos en el torneo
        // 1. obtener los equipos del torneo
        // 2. obtener los partidos del torneo
        // 3. obtener los puntos de cada equipo
        // 4. ordenar los equipos por puntos
        // 5. generar la tabla de posiciones

    }
}
