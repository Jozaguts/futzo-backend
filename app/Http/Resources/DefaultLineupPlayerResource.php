<?php

namespace App\Http\Resources;

use App\Models\DefaultLineupPlayer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DefaultLineupPlayer */
class DefaultLineupPlayerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'default_lineup_id' => $this->default_lineup_id,
            'player_id' => $this->player_id,
            'position_id' => $this->position_id,

            'defaultLineup' => new DefaultLineupResource($this->whenLoaded('defaultLineup')),
        ];
    }
}
