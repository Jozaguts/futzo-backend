<?php

namespace App\Http\Resources;

use App\Models\DefaultLineup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DefaultLineup */
class DefaultLineupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'formation' => $this->resource->defaultLineup->formation->value,
            'goalkeeper' => $this->resource->defaultlineup,
            'defenses' => $this->updated_at,
            'midfielders' => $this->updated_at,
            'forwards' => $this->updated_at
        ];
    }
}
