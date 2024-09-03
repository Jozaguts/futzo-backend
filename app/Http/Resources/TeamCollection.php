<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TeamCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        $this->collection->transform(function ($team) {
            $team->slug = str($team->name)->slug('-');
            return $team;
        });
        return [
            'teams' => $this->collection
        ];
    }
}
