<?php

namespace App\Http\Requests;

use App\Rules\IsImageOrUrl;
use Illuminate\Foundation\Http\FormRequest;

class TeamUpdateRequest extends FormRequest
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
        $request = $this;
        return [
                'team.name' => 'required',
                'team.address' => 'nullable|json',
                'team.image' => [
                    'nullable',
                    new IsImageOrUrl,
                ],
                'team.colors' => 'required|json',
                'team.colors.home' => 'nullable|json',
                'team.colors.home.primary' => 'nullable|string',
                'team.colors.home.secondary' => 'nullable|string',
                'team.colors.away' => 'nullable|json',
                'team.colors.away.primary' => 'nullable|string',
                'team.colors.away.secondary' => 'nullable|string',
                'team.category_id' => 'required|exists:categories,id',
                'team.tournament_id' => 'required|exists:tournaments,id',

                'president.name' => 'required|string',
                'president.avatar' => ['nullable', new IsImageOrUrl],

                'coach.name' => 'required|string',
                'coach.avatar' => ['nullable', new IsImageOrUrl],

        ];
    }
}
