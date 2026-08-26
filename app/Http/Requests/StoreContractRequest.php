<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'asset_id' => ['required', 'exists:assets,id'],
            'start_at' => ['required', 'date'],
            'expected_end_at' => ['required', 'date', 'after:start_at'],
            'deposit_amount' => ['required', 'numeric', 'min:0'],
            'deposit_method' => ['required', Rule::in(['card_hold', 'check', 'cash'])],
            'deposit_status' => ['required', Rule::in(['pending', 'secured', 'returned', 'partially_retained', 'fully_retained'])],
            'checkout_notes' => ['nullable', 'string'],
            'initial_condition' => ['required', 'array'],
            'initial_condition.mileage' => ['required', 'integer', 'min:0'],
            'initial_condition.fuel_level' => ['required', 'string', 'max:50'],
            'initial_condition.damages' => ['nullable', 'string'],
            'options' => ['nullable', 'array'],
            'options.*.name' => ['required_with:options', 'string', 'max:255'],
            'options.*.description' => ['nullable', 'string'],
            'options.*.quantity' => ['required_with:options', 'integer', 'min:1'],
            'options.*.unit_price' => ['required_with:options', 'numeric', 'min:0'],
        ];
    }
}
