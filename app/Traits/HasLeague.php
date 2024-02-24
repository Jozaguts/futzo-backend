<?php

namespace App\Traits;

trait HasLeague
{
    public static function bootHasLeague()
    {
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->league_id = auth()->user()->league_id;
            }
        });
    }
}
