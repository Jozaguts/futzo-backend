<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportPlayerRequest extends FormRequest
{
	public function rules(): array
	{
		return [
			'file' => 'required|file|xlx,xls,xlsx',
		];
	}

	public function authorize(): bool
	{
		return true;
	}
}
