<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Formation extends Model
{
    use HasFactory;
    protected $fillable = ['name','defenses', 'midfielders', 'forwards'];
    protected $hidden = ['created_at', 'updated_at'];

}
