<?php

namespace App\Http\Requests;

use App\CreditCardInstallmentCalculator;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class CreateAsaasCreditCardPaymentRequest extends FormRequest
{
    /** @var list<string> */
    protected $dontFlash = [
        'credit_card_number',
        'credit_card_cvv',
    ];

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
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $product = $this->route('product');
        $maximumInstallments = app(CreditCardInstallmentCalculator::class)->maximumFor($this->cardPrice($product));

        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email:rfc', 'max:255'],
            'customer_cpf_cnpj' => ['required', 'string', 'regex:/^\d{11}$|^\d{14}$/'],
            'customer_phone' => ['required', 'string', 'regex:/^\d{10,11}$/'],
            'postal_code' => ['required', 'string', 'regex:/^\d{8}$/'],
            'address_number' => ['required', 'string', 'max:20'],
            'address_complement' => ['nullable', 'string', 'max:100'],
            'credit_card_holder_name' => ['required', 'string', 'max:255'],
            'credit_card_number' => ['required', 'string', 'regex:/^\d{13,19}$/'],
            'credit_card_expiry_month' => ['required', Rule::in(range(1, 12))],
            'credit_card_expiry_year' => ['required', 'integer', 'min:'.now()->year, 'max:'.(now()->year + 20)],
            'credit_card_cvv' => ['required', 'string', 'regex:/^\d{3,4}$/'],
            'installments' => ['required', 'integer', "between:1,{$maximumInstallments}"],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_cpf_cnpj.regex' => 'Informe um CPF (11 números) ou CNPJ (14 números) válido.',
            'customer_phone.regex' => 'Informe um celular com DDD.',
            'postal_code.regex' => 'Informe um CEP com 8 números.',
            'credit_card_number.regex' => 'Informe um número de cartão válido.',
            'credit_card_cvv.regex' => 'Informe um CVV válido.',
            'installments.between' => 'Para este valor, escolha entre 1 e :max parcelas.',
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
            'credit_card_number' => preg_replace('/\D/', '', (string) $this->input('credit_card_number')),
            'credit_card_cvv' => preg_replace('/\D/', '', (string) $this->input('credit_card_cvv')),
        ]);
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            redirect()->to($this->getRedirectUrl())
                ->withErrors($validator, $this->errorBag)
                ->withInput(Arr::except($this->input(), $this->dontFlash))
        );
    }

    private function cardPrice(mixed $product): float
    {
        if ($product instanceof Product) {
            return (float) $product->marketplace_price;
        }

        $cart = Cart::query()
            ->where('user_id', $this->user()?->id)
            ->with('items.product:id,marketplace_price')
            ->first();

        return $cart?->items->sum(
            fn ($item): float => (float) ($item->product?->marketplace_price ?? 0)
        ) ?? 0;
    }
}
