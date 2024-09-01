<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CategoryTeam extends Pivot
{
    use HasFactory;
    
    protected $fillable = ['category_id', 'team_id'];

}
