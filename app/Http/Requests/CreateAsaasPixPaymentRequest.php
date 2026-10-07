<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateAsaasPixPaymentRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'min:2', 'max:150'],
            'customer_email' => ['required', 'email:rfc', 'max:150'],
            'customer_cpf_cnpj' => ['required', 'regex:/^\d{11}(\d{3})?$/'],
            'customer_phone' => ['required', 'string', 'regex:/^\d{10,11}$/'],
            'postal_code' => ['required', 'string', 'regex:/^\d{8}$/'],
            'address_number' => ['required', 'string', 'max:20'],
            'address_complement' => ['nullable', 'string', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'customer_name' => trim((string) $this->input('customer_name')),
            'customer_email' => trim((string) $this->input('customer_email')),
            'customer_cpf_cnpj' => preg_replace('/\D/', '', (string) $this->input('customer_cpf_cnpj')),
            'customer_phone' => preg_replace('/\D/', '', (string) $this->input('customer_phone')),
            'postal_code' => preg_replace('/\D/', '', (string) $this->input('postal_code')),
            'address_number' => trim((string) $this->input('address_number')),
            'address_complement' => trim((string) $this->input('address_complement')),
        ]);
    }
}
