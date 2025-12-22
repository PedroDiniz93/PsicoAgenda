<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PsychologistProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'timezone' => ['required', 'timezone:all'],
            'session_duration' => ['required', 'integer', 'min:15', 'max:240'],
            'allow_online' => ['required', 'boolean'],
            'allow_in_person' => ['required', 'boolean'],
        ];
    }
}
