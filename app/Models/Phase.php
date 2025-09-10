<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Phase extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'is_active',
        'is_completed',
        'min_teams_for'
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_completed' => 'boolean',
        ];
    }
}
