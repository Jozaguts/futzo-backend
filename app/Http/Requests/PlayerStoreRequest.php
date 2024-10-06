<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlayerStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'basic.name' => 'required|string',
            'basic.last_name' => 'required|string',
            'basic.birthdate' => 'required|date',
            'basic.nationality' => 'nullable|string',
            'basic.team_id' => 'nullable|integer',
            'basic.category_id' => 'nullable|integer',
            'basic.image' => 'nullable|image',
            'details.position' => 'nullable|string',
            'details.number' => 'nullable|integer',
            'details.height' => 'nullable|numeric',
            'details.weight' => 'nullable|numeric',
            'details.dominant_foot' => 'nullable|string',
            'details.medical_notes' => 'nullable|string',
            'contact.email' => 'required|email',
            'contact.phone' => 'nullable|string',
            'contact.notes' => 'nullable|string',
        ];
    }
}
