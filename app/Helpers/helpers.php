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
