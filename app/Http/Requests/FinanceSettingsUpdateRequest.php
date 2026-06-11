<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FinanceSettingsUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pix_key_type' => ['nullable', 'in:cpf,cnpj,email,phone,random'],
            'pix_key' => ['nullable', 'string', 'max:255'],
            'default_payment_link' => ['nullable', 'url', 'max:2048'],
            'receipt_prefix' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9-]+$/'],
            'payment_terms_days' => ['required', 'integer', 'min:0', 'max:90'],
            'charge_message_template' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'default_payment_link.url' => 'Informe um link de pagamento válido.',
            'receipt_prefix.regex' => 'Use apenas letras, números e hífen no prefixo do recibo.',
            'payment_terms_days.max' => 'O prazo de pagamento deve ter no máximo 90 dias.',
        ];
    }
}
