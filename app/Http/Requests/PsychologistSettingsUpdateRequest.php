<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PsychologistSettingsUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'google_calendar_token' => ['nullable', 'string'],
            'whatsapp_confirm_enabled' => ['sometimes', 'boolean'],
            'whatsapp_confirm_days_before' => ['sometimes', 'integer', 'min:0', 'max:30'],
            'whatsapp_sender_phone_id' => ['nullable', 'string', 'max:100', 'regex:/^\d+$/'],
            'whatsapp_sender_display_number' => ['nullable', 'string', 'max:30'],
            'email_confirm_enabled' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'whatsapp_sender_phone_id.regex' => 'Informe apenas os números do Phone Number ID do WhatsApp.',
        ];
    }
}
