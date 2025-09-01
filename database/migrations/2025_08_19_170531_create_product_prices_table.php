<?php

use App\Models\Currency;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_prices', static function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Product::class)->constrained('products');
            $table->foreignIdFor(Currency::class)->constrained('currencies');
            $table->enum('billing_period', ['month', 'year','one_time']);
            $table->string('plan_slug');
            $table->bigInteger('price');
            $table->string('variant');
            $table->string('stripe_price_id')->nullable();
            $table->string('stripe_product_id')->nullable();
            $table->boolean('active')->default(false);
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
