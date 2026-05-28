<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PatientRecordStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recorded_at' => ['nullable', 'date'],
            'title' => ['required', 'string', 'max:190'],
            'notes' => ['nullable', 'string'],
            'treatment_objectives' => ['nullable', 'array'],
            'treatment_objectives.*' => ['nullable', 'string', 'max:500'],
            'techniques' => ['nullable', 'array'],
            'techniques.*' => ['nullable', 'string', 'max:500'],
            'homework_items' => ['nullable', 'array'],
            'homework_items.*.id' => ['nullable', 'string'],
            'homework_items.*.description' => ['nullable', 'string', 'max:500'],
            'homework_items.*.status' => ['nullable', Rule::in(['pending', 'in_progress', 'done'])],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimetypes:application/pdf,audio/mpeg,audio/mp3,audio/wav,audio/x-wav,audio/aac,audio/m4a', 'max:12288'],
            'existing_attachments' => ['nullable', 'array'],
            'existing_attachments.*' => ['string'],
        ];
    }
}
