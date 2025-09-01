<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Lightweight faker replacement safe for production.
 * Only provides the minimal surface used by factories/seeders.
 */
class Fake
{
    private static array $firstNames = [
        'Juan','Carlos','Luis','Miguel','Jose','Diego','Jorge','Andres','Fernando','Sergio',
        'Ana','Maria','Laura','Sofia','Lucia','Paula','Carla','Elena','Gabriela','Camila',
    ];
    private static array $lastNames = [
        'Gonzalez','Rodriguez','Lopez','Martinez','Hernandez','Perez','Sanchez','Ramirez','Cruz','Flores',
    ];
    private static array $words = [
        'futbol','liga','torneo','equipo','jugador','cancha','temporada','gol','pase','ataque','defensa','entrenador',
        'balon','estrategia','victoria','partido','final','semifinal','copa','clasico','rival','tabla','puntos','grupo'
    ];

    public static function firstName(): string
    {
        return Arr::random(self::$firstNames);
    }

    public static function lastName(): string
    {
        return Arr::random(self::$lastNames);
    }

    public static function name(): string
    {
        return self::firstName().' '.self::lastName();
    }

    public static function company(): string
    {
        $prefix = Arr::random(['Club','Deportivo','Atletico','Union','Real','CF','SC']);
        $core = ucfirst(self::domainWord());
        return "$prefix $core";
    }

    public static function word(): string
    {
        return Arr::random(self::$words);
    }

    public static function words(int $nb = 3): array
    {
        $out = [];
        for ($i = 0; $i < $nb; $i++) {
            $out[] = self::word();
        }
        return $out;
    }

    public static function sentence(int $words = 10): string
    {
        return ucfirst(implode(' ', self::words($words))).'.';
    }

    public static function text(int $max = 200): string
    {
        $t = '';
        while (strlen($t) < $max) {
            $t .= ' '.self::sentence(random_int(6, 12));
        }
        return trim(substr($t, 0, $max));
    }

    public static function paragraph(int $sentences = 3): string
    {
        $out = [];
        for ($i = 0; $i < $sentences; $i++) {
            $out[] = self::sentence(random_int(6, 12));
        }
        return implode(' ', $out);
    }

    public static function imageUrl(int $width = 640, int $height = 480, ...$ignored): string
    {
        return "https://picsum.photos/{$width}/{$height}?random=".random_int(1, 1000000);
    }

    public static function numberBetween(int $min, int $max): int
    {
        return random_int($min, $max);
    }

    public static function randomNumber(int $digits = 2): int
    {
        $max = (int) pow(10, max(1, $digits)) - 1;
        return random_int(0, $max);
    }

    public static function boolean(): bool
    {
        return (bool) random_int(0, 1);
    }

    public static function randomElement(array $arr)
    {
        return Arr::random($arr);
    }

    public static function safeEmail(): string
    {
        return 'user'.bin2hex(random_bytes(3)).'@example.com';
    }

    public static function phoneNumber(): string
    {
        return '+52 '.self::numerify('### ### ## ##');
    }

    public static function numerify(string $pattern): string
    {
        return preg_replace_callback('/#/', function () { return (string) random_int(0, 9); }, $pattern);
    }

    public static function date(string $format = 'Y-m-d'): string
    {
        return Carbon::now()->subDays(random_int(0, 365))->format($format);
    }

    public static function dateTime(): \DateTimeInterface
    {
        return Carbon::now()->subDays(random_int(0, 30));
    }

    public static function domainWord(): string
    {
        return Str::slug(self::word().'-'.Str::lower(Str::random(3)));
    }

    public static function country(): string
    {
        $countries = ['México','Argentina','Chile','Colombia','Perú','España','Brasil','Uruguay','Ecuador','Paraguay'];
        return Arr::random($countries);
    }
}

