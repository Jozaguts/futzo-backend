<?php

namespace App\Http\Resources;

use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Currency */
class currencyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'symbol' => $this->symbol,
            'iso_code' => $this->iso_code,
            'payment_gateway' => $this->payment_gateway,
            'is_default' => $this->is_default,
            'properties' => $this->properties,
        ];
    }
}
