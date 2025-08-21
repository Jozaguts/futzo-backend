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
                  'prices' => $item->productPrices->mapWithKeys(function ($price) use($item) {
                      $monthlyPrice =number_format($item->productPrices->where('billing_period','monthly')->pluck('price')->first() /100,2,'.',',');
                      $formatPrice = number_format(
                          $price->billing_period === 'monthly'
                              ? $price->price  / 100
                              : ($price->price / 12) / 100
                          ,2,'.',',');
                      return [
                          $price->billing_period => [
                              'price' => $price->billing_period === 'monthly'
                                  ? "<p><span class='price-text'>{$price->currency->symbol}</span><span class='price-text'>{$formatPrice}</span><small class='price-details'>{$price->currency->iso_code}/mes</small></p>"
                                    : "<p ><span class='price-text'>{$price->currency->symbol}</span><span class='price-text' style='text-decoration: line-through;'>{$monthlyPrice}</span></><small class='price-details'>{$price->currency->iso_code}/mes</small></p>",
                              'symbol' => $price->currency->symbol,
                              'iso_code' => $price->currency->iso_code,
                              'promo' =>  $this->getPromo( $item->sku, $price->billing_period, $monthlyPrice),
                              'cta' => $this->getCTA($item->sku),
                              'url' => config('app.frontend_url') . "/suscripcion?plan=$item->sku",
                          ]
                      ];
                  }),
              ]
            ];
        })->toArray();
    }
    private function getPromo($sku, $billing_period, $price): string
    {
        $promo = '';
         if($sku === 'kickoff'){
            $promo = $billing_period === 'monthly' ? 'Primer mes $299 MXN. <br> Empieza simple, juega en serio.' : "<strong style='font-size: 18px;'>$439.00 MXN</strong> / mes<br>(ahorra 12%)";
        } else if($sku ==='pro_play') {
             $promo =  $billing_period === 'monthly' ? 'Primer mes $299 MXN. <br> Inscribe equipos con un clic.' : "<strong style='font-size: 18px;'>$639 MXN </strong> / mes <br>(ahorra 20%)";
         }
        else if ($sku ==='elite_league'){
            $promo = $billing_period === 'monthly' ? 'Primer mes $699 MXN. <br> Soporte prioritario sin límites.' : "<strong style='font-size: 18px;'> $1,274 MXN </strong> /mes <br>(ahorra 15%)";
        }
        return $promo;
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
