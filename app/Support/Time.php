<?php

namespace App\Support;

class Time
{
    public static function toMinutes(string $hhmm): int
    {
        [$h, $m] = array_map('intval', explode(':', $hhmm));
        return $h * 60 + $m;
    }

    public static function toHHMM(int $minutes): string
    {
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return sprintf('%02d:%02d', $h, $m);
    }

    public static function snap(int $minutes, int $stepMinutes): int
    {
        return intdiv($minutes, $stepMinutes) * $stepMinutes;
    }
}

