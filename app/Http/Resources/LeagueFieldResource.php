<?php

namespace App\Http\Resources;

use App\Models\LeagueField;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LeagueField */
class LeagueFieldResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'league_id' => $this->league_id,
            'field_id' => $this->field_id,

            'field' => new FieldResource($this->whenLoaded('field')),
        ];
    }
}
