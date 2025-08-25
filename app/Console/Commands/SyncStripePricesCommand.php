<?php

namespace App\Console\Commands;

use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class SyncStripePricesCommand extends Command
{
    protected $signature = 'sync:stripe-prices';

    protected $description = 'Sync product prices from Stripe into local database';

    public function handle(): void
    {
        try {
            $stripe = new StripeClient(config('services.stripe.secret'));
            DB::transaction( function () use ($stripe) {
                $prices = $stripe->prices->all([
                    'expand' => ['data.product'],
                    'limit' => 100,
                    'active' => true,
                ]);
                foreach ($prices->data as $price) {
                    $stripeProduct = $price->product;
                    $amount = $price->unit_amount;
                    $iso_code = $price->currency;
                    $currency = Currency::whereLike('iso_code', $iso_code)->first();
                    $product = Product::where('sku', $stripeProduct->metadata?->sku)->firstOrFail();
                    if (is_null($product)){
                        $this->error('Product not found');
                    }
                    ProductPrice::updateOrCreate(
                        ['stripe_price_id' => $price->id],
                        [
                            'product_id' => $product->id,
                            'stripe_product_id' => is_string($stripeProduct) ? $stripeProduct : $stripeProduct->id,
                            'currency_id' => $currency?->id,
                            'billing_period' => $price->recurring['interval'] ?? 'one_time',
                            'price' => $amount,
                            'plan_slug' => $price->lookup_key,
                            'variant' => $price->metadata->variant,
                            'active' => $price->active,
                        ]
                    );
                }
            });

        }catch (ApiErrorException $e){
            $this->error('SyncStripePricesCommand Stripe API Error: '. $e->getMessage());
        } catch (\Throwable $e) {
            $this->error('Throwable Stripe API Error: '. $e->getMessage());
        }
    }
}
