<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlayerUpdateRequest extends FormRequest
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
    public function rules()
    {
        $player = $this->route('player');
        $userId = $player?->user_id;

        return [
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'nullable',
                'email',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone' => [
                'sometimes',
                'nullable',
                'string',
                Rule::unique('users', 'phone')->ignore($userId),
            ],
            'birthdate' => ['sometimes', 'nullable', 'date'],
            'nationality' => ['sometimes', 'nullable', 'string', 'max:255'],
            'position_id' => ['sometimes', 'nullable', 'exists:positions,id'],
            'number' => ['sometimes', 'nullable', 'integer'],
            'height' => ['sometimes', 'nullable', 'numeric'],
            'weight' => ['sometimes', 'nullable', 'numeric'],
            'dominant_foot' => ['sometimes', 'nullable', 'string', 'max:255'],
            'medical_notes' => ['sometimes', 'nullable', 'string'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'team_id' => ['prohibited'],
            'category_id' => ['prohibited'],
        ];
    }
}
