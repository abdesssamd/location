<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date'],
            'expected_end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'description' => ['required', 'string'],
            'status' => ['required', Rule::in(['scheduled', 'in_progress', 'completed'])],
        ];
    }
}
