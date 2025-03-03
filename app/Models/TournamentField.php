<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TournamentField extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tournament_id',
        'field_id',
        'availability',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
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
