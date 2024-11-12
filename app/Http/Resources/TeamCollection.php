<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TeamCollection extends ResourceCollection
{
    protected mixed $categories;
    protected mixed $tournaments;

    public function __construct($resource)
    {
        parent::__construct($resource);


    }

    public function toArray(Request $request): array
    {
        return [
            'teams' => $this->collection->map(function ($team) {
                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'address' => $team->address,
                    'slug' => str($team->name)->slug('-'),
                    'email' => $team->email,
                    'phone' => $team->phone,
                    'description' => $team->description,
                    'image' => $team->image,
                    'president' => $team->president,
                    'coach' => $team->coach,
                    'category' => $team->categories()->where('team_id', $team->id)->first(),
                    'tournament' => $team->tournaments()->where('team_id', $team->id)->first(),
                    'colors' => $team->colors,
                ];
            }),

        ];
    }
}
