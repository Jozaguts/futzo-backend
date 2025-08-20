<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/** @mixin Product */
class ProductCollection  extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return $this->collection->mapWithKeys(function ($item) {
            return [
              $item->sku => [
                  'sku' => $item->sku,
                  'name' => $item->name,
                  'monthly_price' => number_format($item->productPrices->where('billing_period','monthly')->first()->price / 100,2,'.',','),
                  'annually_price' => number_format(($item->productPrices->where('billing_period','annually')->first()->price / 12) / 100,2,'.',','),
                  'cta' => $this->getCTA($item->sku),
              ]
            ];
        })->toArray();
    }
    private function getCTA(string $sku): string
    {
        return match ($sku) {
            'kickoff' => 'Empieza Simple',
            'pro_play' => '🔥 Comienza Ahora',
            'elite_league' => 'Plan Premium',
        };
    }
}
