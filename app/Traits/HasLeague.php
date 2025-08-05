<?php

namespace App\Traits;

/**
 * @method static creating(\Closure $param)
 */
trait HasLeague
{
    public static function bootHasLeague(): void
    {
        static::creating(static function ($model) {
            if (auth()->check()) {
                $model->league_id = auth()->user()->league_id;
            }
        });
    }
}
