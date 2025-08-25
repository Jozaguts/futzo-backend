<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends Model
{
    protected $fillable = [
        'product_id',
        'currency_id',
        'price',
        'billing_period',
        'stripe_price_id',
        'stripe_product_id',
        'active',
        'currency',
        'variant',
        'plan_slug'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public static function for(string $sku, string $variant, string $period): ?self {
        return static::whereHas('product', static fn($q)=>$q->where('sku',$sku))
            ->where('variant',$variant)
            ->where('billing_period',$period)
            ->first();
    }

}
