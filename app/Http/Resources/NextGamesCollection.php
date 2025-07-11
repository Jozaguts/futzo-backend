<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class NextGamesCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->count() ? $this->collection->map(function ($item) {
                return new NextGamesResource($item);
            }) : []
        ];
    }
}
