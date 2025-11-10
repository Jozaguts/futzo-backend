<?php

namespace App\Http\Resources;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class PlayerResource extends JsonResource
{
    public static $wrap = 'data';
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $defaultStats = [
            'games_played' => 0,
            'games' => 0,
            'tournaments' => 0,
            'tournaments_played' => 0,
            'goals' => 0,
            'assists' => 0,
            'fouls' => 0,
            'fouls_committed' => 0,
            'yellow_cards' => 0,
            'red_cards' => 0,
            'own_goals' => 0,
            'minutes_played' => 0,
            'clean_sheets' => 0,
        ];

        $attributes = match (true) {
            $this->resource instanceof EloquentModel => $this->resource->getAttributes(),
            is_array($this->resource) => $this->resource,
            default => [],
        };

        $stats = Arr::get($attributes, 'stats_payload', $defaultStats);
        $teams = Arr::get($attributes, 'teams_payload', []);
        $tournaments = Arr::get($attributes, 'tournaments_payload', []);

        $user = $this->whenLoaded('user');
        $team = $this->whenLoaded('team');
        $position = $this->whenLoaded('position');
        $category = $this->whenLoaded('category');

        $fullNameParts = array_filter([$user?->name, $user?->last_name]);
        $teamPayload = null;

        if ($team) {
            $teamPayload = [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'image' => $team->image,
                'category' => $category ? [
                    'id' => $category->id,
                    'name' => $category->name,
                ] : null,
                'tournaments' => $tournaments,
            ];
        }

        return [
            'id' => $this->id,
            'name' => $user?->name,
            'last_name' => $user?->last_name,
            'full_name' => trim(implode(' ', $fullNameParts)),
            'email' => $user?->email,
            'phone' => $user?->phone,
            'iso_code' => null,
            'notes' => $this->getAttribute('notes'),
            'image' => $user?->image,
            'birthdate' => $this->birthdate?->toDateString(),
            'birthdate_label' => $this->birthdate?->translatedFormat('d MMM YYYY'),
            'age' => $this->birthdate?->age,
            'nationality' => $this->nationality,
            'height' => $this->height,
            'weight' => $this->weight,
            'number' => $this->number,
            'dominant_foot' => $this->dominant_foot,
            'medical_notes' => $this->medical_notes,
            'team' => $teamPayload,
            'teams' => $teams,
            'position' => $position ? [
                'id' => $position->id,
                'name' => $position->name,
                'abbr' => $position->abbr,
            ] : null,
            'category' => $category ? [
                'id' => $category->id,
                'name' => $category->name,
            ] : null,
            'stats' => $stats,
            'tournaments' => $tournaments,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
