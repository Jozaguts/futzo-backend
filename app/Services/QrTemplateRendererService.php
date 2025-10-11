<?php

namespace App\Services;

use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\ImageManager;
use Endroid\QrCode\Color\Color;
use Intervention\Image\Colors\Rgb\Color as RgbColor;
class QrTemplateRendererService
{
    public static function render(array $data, $width = 1080, $height = 1080): string
    {
        $manager = ImageManager::gd();
        $qrCode = new QrCode(
            data: $data['qr_value'] ?? 'https://futzo.io',
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 350,
            margin: 1,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(...self::hexToRgb($data['foreground_color'] ?? '#000000')),
            backgroundColor: new Color(...self::hexToRgb($data['background_color'] ?? '#FFFFFF')),
        );

        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        $qrBinary = $result->getString();

        // 2️⃣ Crear canvas de fondo
        [$r, $g, $b] = self::hexToRgb($data['background_color'] ?? '#FFFFFF');

        $canvas = $manager->create(800, 800)->fill(new RgbColor($r, $g, $b));

        // 3️⃣ Leer QR en Intervention y colocarlo en el centro
        $qrImage = $manager->read($qrBinary);
        $canvas->place($qrImage, 'bottom-right', 32, 96);


        // set title
        if (!empty($data['title'])){
            $canvas->text($data['title'], 32, 32,
                function($font) use ($data){
                $font->file(public_path('fonts/Inter/static/Inter_24pt-Regular.ttf'));
                $font->size(70);
                $font->color($data['foreground_color'] ?? '#555555');
                $font->align('left');
                $font->wrap(736);
                $font->valign('top');
            });
        }
        if (!empty($data['subtitle'])) {
            $canvas->text($data['subtitle'], 32, 100,
                function ($font) use ($data) {
                $font->file(public_path('fonts/Inter/static/Inter_24pt-Bold.ttf'));
                $font->size(70);
                $font->color($data['foreground_color'] ?? '#555555');
                $font->align('left');
                $font->wrap(736);
                $font->valign('top');
            });
        }
        if (!empty($data['description'])) {
            $canvas->text($data['description'], 32,  600,
                function ($font) use ($data) {
                $font->file(public_path('fonts/Inter/static/Inter_28pt-LightItalic.ttf'));
                $font->size(50);
                $font->color($data['foreground_color'] ?? '#555555');
                $font->align('left');
                $font->wrap(400);
                $font->valign('top');
            });
        }
        if (!empty($data['logo'])) {
            try {
                $logoPath = public_path($data['logo']);
                if (file_exists($logoPath)) {
                    $logo = $manager->read($logoPath)->scaleDown(200);
                    $canvas->place($logo, 'bottom', 0, 0,30);
                } else {
                    Log::warning("Logo no encontrado: {$logoPath}");
                }
            } catch (\Throwable $e) {
                Log::error("Error al cargar logo QR: {$e->getMessage()}");
            }
        }

        return $canvas->encode(new PngEncoder())->toDataUri();

    }

    private static function hexToRgb(string $hex): array
    {
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) === 3) {
            $hex = preg_replace('/(.)/', '$1$1', $hex);
        }
        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }
}
