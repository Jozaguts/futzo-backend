<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeagueField extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'league_id',
        'field_id',
        'availability',
    ];

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class);
    }

    protected function casts(): array
    {
        return [
            'availability' => 'array',
        ];
    }
}
