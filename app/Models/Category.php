<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['name','age_range','gender'];

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }
    public function tournaments(): BelongsToMany
    {
        return $this->belongsToMany(Tournament::class,'category_team');
    }
}
