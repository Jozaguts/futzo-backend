<?php

namespace App\Observers;

use App\Models\League;
use App\Models\QrType;

class LeagueObserver
{
    public function created(League $league): void
    {
        if ($league->QRConfigurations()->exists()) {
            return;
        }

        QrType::all()->each(function ($qrType) use ($league) {
            $league->QRConfigurations()->create([
                'qr_type_id' => $qrType->id,
                'title' => $league->name,
                'subtitle' => 'Configuración inicial',
                'description' => $qrType->description,
                'background_color' => '#F4F5FA',
                'foreground_color' => '#2E263D',
                'primary_color' => '#7E4EE6',
                'font_family' => 'Inter_24pt-Regular.ttf',
                'font_size_title' => 48,
                'font_size_subtitle' => 32,
                'font_size_description' => 24,
                'logo_path' => 'images/vertical/logo-09.png',
                'layout' => 'square',
            ]);
        });
    }
}
