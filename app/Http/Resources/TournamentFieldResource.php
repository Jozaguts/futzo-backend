<?php

namespace App\Http\Resources;

use App\Models\TournamentField;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TournamentField */
class TournamentFieldResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'tournament_id' => $this->tournament_id,
            'field_id' => $this->field_id,

            'field' => new FieldResource($this->whenLoaded('field')),
        ];
    }
}
