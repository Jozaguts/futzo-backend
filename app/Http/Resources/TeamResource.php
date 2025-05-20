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
            'id' => $this->id,
            'name' => $this->name,
            'colors' => $this->colors,
            'president' => $this->president()->select('id', 'name', 'email', 'phone')->first(),
            'coach' => $this->coach()->select('id', 'name', 'email', 'phone')->first(),
            'league' => $this->leagues()->where('league_id', auth()?->user()?->league_id)->select('leagues.id', 'leagues.name')->first(),
        ];
    }
}
