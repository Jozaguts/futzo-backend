<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Currency extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'symbol',
        'iso_code',
        'payment_gateway',
        'is_default',
        'properties',
        'usd_rate_exchange',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'properties' => 'array',
        ];
    }
}
