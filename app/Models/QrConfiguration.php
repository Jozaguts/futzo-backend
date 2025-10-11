<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrConfiguration extends Model
{
    protected $fillable = [
        'league_id',
        'qr_type_id',
        'title',
        'subtitle',
        'description',
        'background_color',
        'foreground_color',
        'logo_path',
        'font_family',
        'font_size_title',
        'font_size_subtitle',
        'font_size_description',
        'layout',
    ];

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function qrType(): BelongsTo
    {
        return $this->belongsTo(QrType::class);
    }
}
