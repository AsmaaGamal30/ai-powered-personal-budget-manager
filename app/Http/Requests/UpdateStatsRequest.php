<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStatsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => 'sometimes|exists:categories,id',
            'amount' => 'sometimes|numeric|min:0.01',
            'date' => 'sometimes|date',
            'time' => 'nullable|date_format:H:i:s',
            'stats_type' => 'sometimes|in:daily,weekly,monthly,quarterly,yearly',
            'description' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.exists' => 'The selected category does not exist.',
            'amount.numeric' => 'The amount must be a number.',
            'amount.min' => 'The amount must be at least 0.01.',
            'date.date' => 'Please enter a valid date.',
            'time.date_format' => 'The time must be in the format HH:MM:SS.',
            'stats_type.in' => 'The statistics type must be one of: daily, weekly, monthly, quarterly, yearly.',
            'description.max' => 'The description may not be greater than 500 characters.',
        ];
    }
}
