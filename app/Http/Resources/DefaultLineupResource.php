<?php

namespace App\Http\Resources;

use App\Models\DefaultLineup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DefaultLineup */
class DefaultLineupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'formation' => $this->resource->defaultLineup->formation->value,
            'goalkeeper' => $this->resource->defaultlineup->players->where('position_id',1)->transform(function ($player) {
                return [
                    'abbr' => $player->position->abbr,
                    'number'=> $player->number,
                    'name'=> $player->user->name,
                    'goals'=> 0,
                    'cards'=> [
                        'red' => false,
                        'yellow'=> false,
                        'doble_yellow_card'=> false
                    ],
                    'substituted' => false
                ];
            })->toArray(),
            'defenses' => $this->updated_at,
            'midfielders' => $this->updated_at,
            'forwards' => $this->updated_at
        ];
    }
}
