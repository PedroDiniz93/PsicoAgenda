<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FinancePaymentUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'paid' => ['sometimes', 'boolean'],
            'paid_at' => ['nullable', 'date'],
            'price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'payment_due_at' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'in:pix,credit_card,debit_card,cash,bank_transfer,insurance,other'],
            'payment_link' => ['nullable', 'url', 'max:2048'],
            'payment_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'price.min' => 'O valor não pode ser negativo.',
            'payment_due_at.date' => 'A data de vencimento é inválida.',
            'payment_method.in' => 'Selecione uma forma de pagamento válida.',
            'payment_link.url' => 'Informe um link de pagamento válido.',
        ];
    }
}
