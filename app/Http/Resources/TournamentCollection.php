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

        $data['tournaments'] =  $this->collection->map(fn ($tournament) => [
            'id' => $tournament->id,
            'name' => $tournament->name,
            'teams' => $tournament->teams_count,
            'players' => $tournament->players_count,
            'matches' => $tournament->games_count,
            'league' => $tournament->league->name,
        ])->toArray();

        return $data;
    }
}
