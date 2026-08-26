<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'actual_end_at' => ['required', 'date'],
            'final_mileage' => ['required', 'integer', 'min:0'],
            'late_penalty_per_day' => ['nullable', 'numeric', 'min:0'],
            'damage_fee_total' => ['nullable', 'numeric', 'min:0'],
            'deposit_retained_amount' => ['nullable', 'numeric', 'min:0'],
            'deposit_retention_reason' => ['nullable', 'string'],
            'return_notes' => ['nullable', 'string'],
            'asset_next_status' => ['required', Rule::in(['available', 'maintenance'])],
            'final_condition' => ['required', 'array'],
            'final_condition.fuel_level' => ['required', 'string', 'max:50'],
            'final_condition.damages' => ['nullable', 'string'],
            'maintenance_expected_end_date' => ['nullable', 'date'],
            'maintenance_cost' => ['nullable', 'numeric', 'min:0'],
            'maintenance_description' => ['nullable', 'string'],
        ];
    }
}
