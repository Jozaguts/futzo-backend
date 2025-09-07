<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class TournamentPhase extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tournament_phases';
    protected $fillable = ['phase_id', 'tournament_id', 'is_active', 'is_completed'];
    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    public function phase(): BelongsTo
    {
        return $this->belongsTo(Phase::class);
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function rules(): HasOne
    {
        return $this->hasOne(TournamentPhaseRule::class, 'tournament_phase_id');
    }

}
