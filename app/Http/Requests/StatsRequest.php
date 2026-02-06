<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StatsRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'period' => 'sometimes|string|in:daily,weekly,monthly,quarterly,yearly',
            'date' => 'sometimes|date_format:Y-m-d',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'period.in' => 'Period must be one of: daily, weekly, monthly, quarterly, yearly',
            'date.date_format' => 'Date must be in YYYY-MM-DD format',
        ];
    }
}
