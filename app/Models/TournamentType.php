<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TournamentType extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'description', 'status'];

    public function tournaments()
    {
        return $this->hasMany(Tournament::class);
    }
}
