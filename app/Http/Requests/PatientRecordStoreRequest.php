<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'notes' => ['required', 'string'],
        ];
    }
}
