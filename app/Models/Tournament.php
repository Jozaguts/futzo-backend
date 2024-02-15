<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tournament extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'name',
        'location',
        'start_date',
        'end_date',
        'prize',
        'winner',
        'description',
        'logo',
        'banner',
        'status'
    ];
}
