<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeagueResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource?->id,
            'name' => $this->resource?->name,
            'description' => $this->resource?->description,
            'creation_date' => $this->resource?->creation_date,
            'logo' => $this->resource?->logo,
            'football_type_id' => $this->resource?->football_type_id,
            'banner' => $this->resource?->banner,
            'status' => $this->resource?->status,
            'owner_id' => $this->resource?->owner_id,
            'location' => $this->resource?->location,
            'tournament_count' => $this->resource?->tournaments_count,
        ];
    }
}
