<?php

namespace App\Http\Resources;

use App\Models\LineupPlayer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LineupPlayer */
class LineupPlayerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'field_location' => $this->field_location,
            'substituted' => $this->substituted,
            'goals' => $this->goals,
            'yellow_card' => $this->yellow_card,
            'red_card' => $this->red_card,
            'doble_yellow_card' => $this->doble_yellow_card,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'lineup_id' => $this->lineup_id,
            'player_id' => $this->player_id,
        ];
    }
}
