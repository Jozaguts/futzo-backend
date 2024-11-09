<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PlayerCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection->map(function ($player) {
            return [
                'id' => $player->id,
                'name' => $player->user->name,
                'last_name' => $player->user->last_name,
                'birthdate' => $player->birthdate,
                'nationality' => $player->nationality,
                'team' => [
                    'id' => $player->team->id,
                    'name' => $player->team->name,
                ],
                'category' => [
                    'id' => $player->category->id,
                    'name' => $player->category->name,
                ],
                'rol' => 'Jugador', // todo
                'position' => [
                    'id' => $player->position->id,
                    'name' => $player->position->name,
                    'abbr' => $player->position->abbr,
                ],
                'number' => $player->number,
                'height' => $player->height,
                'weight' => $player->weight,
            ];
        })->toArray();
    }
}
