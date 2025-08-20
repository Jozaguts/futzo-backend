<?php

use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('product_prices')) {
            Schema::create('product_prices', function (Blueprint $table) {
                $table->id();
                $table->foreignIdFor(Product::class)->constrained('products');
                $table->foreignIdFor(Currency::class)->constrained('currencies');
                $table->enum('billing_period', ['monthly', 'annually']);
                $table->bigInteger('price');
                $table->string('stripe_price_id')->nullable();
                $table->timestamps();
            });
            $productPrices = [
                [
                    'product_id' => 1,
                    'currency_id' => 1,
                    'price' => 49900,
                    'billing_period' => 'monthly',
                ],
                [
                    'product_id' => 1,
                    'currency_id' => 1,
                    'price' => 526800,
                    'billing_period' => 'annually',
                ],
                [
                    'product_id' => 2,
                    'currency_id' => 1,
                    'price' => 79900,
                    'billing_period' => 'monthly',
                ],
                [
                    'product_id' => 2,
                    'currency_id' => 1,
                    'price' => 838800,
                    'billing_period' => 'annually',
                ],
                [
                    'product_id' => 3,
                    'currency_id' => 1,
                    'price' => 149900,
                    'billing_period' => 'monthly',
                ],
                [
                    'product_id' => 3,
                    'currency_id' => 1,
                    'price' => 1498800,
                    'billing_period' => 'annually',
                ],
            ];
            foreach ($productPrices as $productPrice) {
                ProductPrice::updateOrCreate([
                    'product_id' => $productPrice['product_id'],
                    'currency_id' => $productPrice['currency_id'],
                    'billing_period' => $productPrice['billing_period'],
                ],[
                    'price' => $productPrice['price'],
                ]);
            }
        }

    }

    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
