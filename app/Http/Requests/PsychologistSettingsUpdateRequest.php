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
            'email_confirm_enabled' => ['sometimes', 'boolean'],
        ];
    }
}
