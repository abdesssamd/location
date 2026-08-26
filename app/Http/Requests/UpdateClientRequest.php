<?php

namespace App\Http\Requests;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Client $client */
        $client = $this->route('client');

        return [
            'type' => ['required', Rule::in(['individual', 'company'])],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('clients', 'email')->ignore($client->id)],
            'phone' => ['required', 'string', 'max:50'],
            'tax_number' => ['nullable', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:255'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'is_blacklisted' => ['sometimes', 'boolean'],
            'blacklist_reason' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'documents' => ['nullable', 'array'],
            'documents.*.document_type' => ['required_with:documents', 'string', 'max:100'],
            'documents.*.title' => ['required_with:documents', 'string', 'max:255'],
            'documents.*.file' => ['nullable', 'file', 'max:5120'],
            'documents.*.document_number' => ['nullable', 'string', 'max:255'],
            'documents.*.expires_at' => ['nullable', 'date'],
        ];
    }
}
