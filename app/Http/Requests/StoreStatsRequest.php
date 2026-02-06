<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStatsRequest extends FormRequest
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
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'time' => 'nullable|date_format:H:i:s',
            'stats_type' => 'required|in:daily,weekly,monthly,quarterly,yearly',
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
            'amount.required' => 'Please enter an amount.',
            'amount.numeric' => 'The amount must be a number.',
            'amount.min' => 'The amount must be at least 0.01.',
            'date.required' => 'Please enter a date.',
            'date.date' => 'Please enter a valid date.',
            'time.date_format' => 'The time must be in the format HH:MM:SS.',
            'stats_type.required' => 'Please select a statistics type.',
            'stats_type.in' => 'The statistics type must be one of: daily, weekly, monthly, quarterly, yearly.',
            'description.max' => 'The description may not be greater than 500 characters.',
        ];
    }
}