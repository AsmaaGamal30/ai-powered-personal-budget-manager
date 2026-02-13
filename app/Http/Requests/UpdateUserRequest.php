<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'salary' => 'nullable|numeric|min:0',
            'age' => 'nullable|integer|min:0',
            'gender' => 'nullable|string|in:male,female',
            'is_single' => 'nullable|boolean',
            'is_family_provider' => 'nullable|boolean',
            'family_members_count' => 'nullable|integer|min:0|required_if:is_family_provider'
        ];
    }

    public function messages(): array
    {
        return [
            'family_members_count.required_if' => 'The family members count is required when the user is a family provider.',
        ];
    }
}
