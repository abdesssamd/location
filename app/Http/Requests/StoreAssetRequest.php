<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reference' => ['required', 'string', 'max:255', 'unique:assets,reference'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:4096'],
            'daily_rate' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['available', 'rented', 'maintenance', 'inactive'])],
            'extra_attributes.segment' => ['nullable', 'string', 'max:100'],
            'extra_attributes.location' => ['nullable', 'string', 'max:100'],
            'car_detail.license_plate' => ['required', 'string', 'max:255', 'unique:car_details,license_plate'],
            'car_detail.mileage' => ['required', 'integer', 'min:0'],
            'car_detail.fuel_type' => ['required', 'string', 'max:50'],
            'car_detail.transmission' => ['required', 'string', 'max:50'],
            'car_detail.brand' => ['required', 'string', 'max:255'],
            'car_detail.model' => ['required', 'string', 'max:255'],
            'car_detail.color' => ['nullable', 'string', 'max:100'],
            'car_detail.year' => ['nullable', 'integer', 'min:1990', 'max:2100'],
            'car_detail.seats' => ['nullable', 'integer', 'min:1', 'max:99'],
            'documents' => ['nullable', 'array'],
            'documents.*.document_type' => ['required_with:documents', 'string', 'max:100'],
            'documents.*.title' => ['required_with:documents', 'string', 'max:255'],
            'documents.*.file' => ['required_with:documents', 'file', 'max:5120'],
            'documents.*.expires_at' => ['nullable', 'date'],
            'documents.*.notes' => ['nullable', 'string'],
        ];
    }
}
