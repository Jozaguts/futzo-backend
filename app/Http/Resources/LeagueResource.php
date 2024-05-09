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
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'creation_date' => $this->creation_date,
            'logo' => $this->logo,
            'football_type_id' => $this->football_type_id,
            'banner' => $this->banner,
            'status' => $this->status,
            'location' => $this->location,
            'tournament_count' => $this->tournaments_count,
        ];
    }
}
