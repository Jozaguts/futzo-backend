<?php

namespace App\Http\Resources;

use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;

/** @mixin Product */
class ProductCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return $this->collection->mapWithKeys(function (Product|ProductResource $product) {
            $intro_price = $product->productPrices
                ->where('billing_period','month')
                ->where('variant','intro')
                ->firstOrFail();
            $promo_price = $product->productPrices
                ->where('billing_period','month')
                ->where('variant','special')
                ->firstOrFail();
            $annually_price = $product->productPrices
                ->where('billing_period','year')
                ->where('variant','intro')
                ->firstOrFail();

            return [
                $product->sku => [
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'currency' => $intro_price->currency,
                    'annual_saving' => number_format((($intro_price->price / 100) - ($annually_price->price / 100 /12)) * 12, 0,'.',','),
                    'price' => number_format($intro_price->price / 100, 0, '.', ','),
                    'promo_price' => number_format($promo_price->price / 100, 0, '.', ','),
                    'annually_price' => number_format($annually_price->price / 100 / 12, 0, '.', ','),
                    'url' => config('app.frontend_url') . "/suscripcion?plan=$intro_price->plan_slug",
                    'discount' =>  $this->getDiscountPercentage($product->sku),
                    'cta' => $this->getCTA($product->sku),
                ]
            ];
        })->toArray();
    }

    private function getCTA(string $sku): string
    {
        return match ($sku) {
            'kickoff' => 'Empieza Simple',
            'pro_play' => '🔥 Hazlo Pro',
            'elite_league' => 'Juega en la élite',
        };
    }

    private function getDiscountPercentage(string $sku): string
    {
        return match ($sku) {
            'kickoff' => '12%',
            'pro_play' => '20%',
            'elite_league' => '15%',
        };
    }
}
