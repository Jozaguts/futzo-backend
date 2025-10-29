<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PenaltyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Representa un intento individual dentro de la tanda de penales de desempate.
        return [
            'id' => $this->resource->id,
            'player_id' => $this->resource->player_id,
            'team_id' => $this->resource->team_id,
            'score_goal' => (bool) $this->resource->score_goal,
            'kicks_number' => (int) $this->resource->kicks_number,
            'player' => $this->whenLoaded('player', function () {
                $user = $this->player?->user;

                return [
                    'id' => $this->player?->id,
                    'name' => trim(($user->name ?? '') . ' ' . ($user->last_name ?? '')) ?: null,
                ];
            }),
        ];
    }
}
