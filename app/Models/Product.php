<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'sku',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
    public function productPrices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }
}
