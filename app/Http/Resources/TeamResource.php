<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'colors' => $this->resource->colors,
            'image'=> $this->resource->image,
            'president' => $this->resource->president()->select('id', 'name', 'email', 'phone')->first(),
            'coach' => $this->resource->coach()->select('id', 'name', 'email', 'phone')->first(),
            'address' => $this->resource->address['place_id'] ? $this->resource->address : null,
            'tournament' => $this->resource->tournaments()
                ->first(),
            'category' => $this->resource->category()->select('id', 'name')->first(),
            'league' => $this->resource->leagues()->where('league_id', auth()?->user()?->league_id)->select('leagues.id', 'leagues.name')->first(),
        ];
    }
}
