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
            'basic.team_id' => 'nullable|integer|exists:teams,id',
            'basic.category_id' => 'nullable|integer',
            'basic.image' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'details.position_id' => 'nullable|exists:positions,id',
            'details.number' => 'nullable|integer',
            'details.height' => 'nullable|numeric',
            'details.weight' => 'nullable|numeric',
            'details.dominant_foot' => 'nullable|string',
            'details.medical_notes' => 'nullable|string',
            'contact.email' => 'required|email|unique:users,email',
            'contact.phone' => 'nullable|string|unique:users,phone',
            'contact.notes' => 'nullable|string',
        ];
    }

    public function userFormData(): array
    {
        return [
            'name' => $this->validated('basic.name'),
            'last_name' => $this->validated('basic.last_name'),
            'email' => $this->validated('contact.email'),
            'phone' => $this->validated('contact.phone'),
            'image' => $this->validated('basic.image'),
        ];
    }

    public function playerFormData(): array
    {
        return [
            'birthdate' => $this->validated('basic.birthdate'),
            'team_id' => $this->validated('basic.team_id'),
            'category_id' => $this->validated('basic.category_id'),
            'nationality' => $this->validated('basic.nationality'),
            'position_id' => $this->validated('details.position_id'),
            'number' => $this->validated('details.number'),
            'height' => $this->validated('details.height'),
            'weight' => $this->validated('details.weight'),
            'dominant_foot' => $this->validated('details.dominant_foot'),
            'medical_notes' => $this->validated('details.medical_notes'),
//            'notes' => $this->validated('contact.notes'), // todo cambiar para la tabla users
        ];
    }
}
