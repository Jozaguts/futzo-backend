<?php

namespace App\Http\Resources;

use App\Models\GameEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin GameEvent */
class GameEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'minute' => $this->minute,
            'type' => $this->type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'game_id' => $this->game_id,
            'player_id' => $this->player_id,
            'related_player_id' => $this->related_player_id,
            'team_id' => $this->team_id,
        ];
    }
}
