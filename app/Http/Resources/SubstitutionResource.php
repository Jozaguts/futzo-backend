<?php

namespace App\Http\Resources;

use App\Models\Substitution;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Substitution */
class SubstitutionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'minute' => $this->minute,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'game_id' => $this->game_id,
            'team_id' => $this->team_id,
            'player_in_id' => $this->player_in_id,
            'player_out_id' => $this->player_out_id,
        ];
    }
}
