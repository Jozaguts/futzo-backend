<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TournamentQRCodeGenerateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:100',
            'subtitle' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:200',
            'qr_value' => 'required|string',
            'background_color' => 'nullable|string',
            'foreground_color' => 'nullable|string',
            'logo' => 'nullable|string',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
