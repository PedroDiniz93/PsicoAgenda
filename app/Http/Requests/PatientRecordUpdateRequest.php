<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'notes' => ['sometimes', 'required', 'string'],
        ];
    }
}
