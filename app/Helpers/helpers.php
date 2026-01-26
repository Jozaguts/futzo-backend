<?php

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;

if (!function_exists('checkFolderPermissions')) {
    /**
     * Verifica permisos de lectura en un directorio.
     */
    function checkFolderPermissions(string $path, bool $throwException = false): bool
    {
        if (!is_dir($path)) {
            $message = "La ruta [$path] no es un directorio válido.";
            Log::warning($message);

            if ($throwException) {
                throw new \Exception($message);
            }

            return false;
        }

        if (!is_readable($path)) {
            $message = "El directorio [$path] no tiene permisos de lectura.";
            Log::warning($message);

            if ($throwException) {
                throw new \Exception($message);
            }

            return false;
        }

        return true;
    }
}
if(!function_exists('hex2rgb')){
    function hex2rgb ( $hex_color ): false|array
    {
        $values = str_replace( '#', '', $hex_color );
        switch ( strlen( $values ) ) {
            case 3;
                [$r, $g, $b] = sscanf($values, "%1s%1s%1s");
                return [ hexdec( "$r$r" ), hexdec( "$g$g" ), hexdec( "$b$b" ) ];
            case 6;
                return array_map( 'hexdec', sscanf( $values, "%2s%2s%2s" ) );
            default:
                return false;
        }
    }
}
if (!function_exists('whatsappTimestamp')){
    function whatsappTimestamp(CarbonInterface $at, ?CarbonInterface $now = null): string
    {
        $now ??= now();
        // Si viene null/invalid en algún caso, falla temprano o maneja según tu app.
        // if (!$at) return '';

        if ($at->greaterThan($now)) {
            // si tu sistema puede generar timestamps futuros por TZ, decide qué hacer:
            // aquí lo tratamos como "hora" (KISS)
            return $at->isoFormat('h:mm A');
        }

        // < 24h => hora
        if ($at->diffInHours($now) < 24) {
            return $at->isoFormat('h:mm A'); // 9:00 AM / 10:00 PM
        }

        // 24h - < 48h => Ayer
        if ($at->diffInHours($now) < 48) {
            return 'Ayer';
        }

        // 48h - < 7d => día de semana
        if ($at->diffInDays($now) < 7) {
            // 'dddd' => miércoles (según locale)
            // ucfirst para capitalizar: Miércoles
            return ucfirst($at->isoFormat('dddd'));
        }

        // >= 7d => fecha d/m/Y (ej: 16/1/2026)
        return $at->format('j/n/Y');
    }
}
