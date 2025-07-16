<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DefaultLineup extends Model
{
    use HasFactory;
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
    public function formation(): BelongsTo
    {
        return $this->belongsTo(Formation::class);
    }

}
