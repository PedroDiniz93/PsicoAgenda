<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PatientRecordUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recorded_at' => ['sometimes', 'nullable', 'date'],
            'title' => ['sometimes', 'required', 'string', 'max:190'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'treatment_objectives' => ['sometimes', 'nullable', 'array'],
            'treatment_objectives.*' => ['nullable', 'string', 'max:500'],
            'techniques' => ['sometimes', 'nullable', 'array'],
            'techniques.*' => ['nullable', 'string', 'max:500'],
            'homework_items' => ['sometimes', 'nullable', 'array'],
            'homework_items.*.id' => ['nullable', 'string'],
            'homework_items.*.description' => ['nullable', 'string', 'max:500'],
            'homework_items.*.status' => ['nullable', Rule::in(['pending', 'in_progress', 'done'])],
            'attachments' => ['sometimes', 'nullable', 'array'],
            'attachments.*' => ['file', 'mimetypes:application/pdf,audio/mpeg,audio/mp3,audio/wav,audio/x-wav,audio/aac,audio/m4a', 'max:12288'],
            'existing_attachments' => ['sometimes', 'nullable', 'array'],
            'existing_attachments.*' => ['string'],
        ];
    }
}
