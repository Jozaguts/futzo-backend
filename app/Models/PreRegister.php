<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

class PreRegister extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = ['email', 'coupon_id'];

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}
