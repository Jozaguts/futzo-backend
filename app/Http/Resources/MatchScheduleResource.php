<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatchScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        sleep(2);
        return parent::toArray($request);
    }
}
