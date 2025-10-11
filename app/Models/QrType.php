<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QrType extends Model
{
    protected $fillable = [
        'name',
        'key',
        'description',
    ];
}
