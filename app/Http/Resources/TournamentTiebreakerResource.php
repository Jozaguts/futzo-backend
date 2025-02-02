<?php

namespace App\Http\Resources;

use App\Models\TournamentTiebreaker;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TournamentTiebreaker */
class TournamentTiebreakerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rule' => $this->rule,
            'priority' => $this->priority,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'tournament_configuration_id' => $this->tournament_configuration_id,
        ];
    }
}
