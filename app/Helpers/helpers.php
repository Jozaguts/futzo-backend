<?php

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
