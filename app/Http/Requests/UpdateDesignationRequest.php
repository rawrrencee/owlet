<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDesignationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'designation_name' => ['required', 'string', 'max:255'],
            'designation_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('designations', 'designation_code')
                    ->whereNull('deleted_at')
                    ->ignore($this->route('designation')),
            ],
        ];
    }
}
