<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class League extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'name', 'description', 'creation_date', 'logo', 'banner','status','location',
    ];
    protected $casts = [
        'creation_date' => 'datetime',
    ];
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
