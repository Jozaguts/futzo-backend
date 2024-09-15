<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IsImageOrUrl implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value instanceof \Illuminate\Http\UploadedFile) {
            if (!in_array($value->getClientOriginalExtension(), ['jpg', 'png', 'svg'])) {
                $fail('El archivo debe ser de tipo jpg, png o svg.');
            }
        }
        // Si es un string, validar que sea una URL válida
        elseif (is_string($value) && $value !== 'null' && !filter_var($value, FILTER_VALIDATE_URL)) {
            $fail('La URL proporcionada no es válida.');
        }
        // Si no es ni un archivo ni un string, es inválido
        elseif (!is_string($value) && !$value instanceof \Illuminate\Http\UploadedFile) {
            $fail('El campo debe ser una imagen o una URL válida.');
        }
    }
}
