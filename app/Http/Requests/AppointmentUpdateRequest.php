<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AppointmentUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['sometimes', 'required', 'integer', 'exists:patients,id'],
            'start_at' => ['sometimes', 'required', 'date'],
            'end_at' => ['sometimes', 'required', 'date', 'after:start_at'],
            'status' => ['sometimes', 'required', 'in:scheduled,done,missed,canceled'],
            'type' => ['sometimes', 'required', 'in:online,in_person'],
            'price' => ['sometimes', 'nullable', 'numeric'],
            'paid_at' => ['sometimes', 'nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_at.required' => 'Informe a data e horário de início.',
            'start_at.date' => 'A data de início é inválida.',
            'end_at.required' => 'Informe a data e horário de término.',
            'end_at.date' => 'A data de término é inválida.',
            'end_at.after' => 'O horário de término deve ser posterior ao horário inicial.',
            'patient_id.required' => 'Selecione um paciente.',
            'patient_id.exists' => 'Paciente inválido.',
        ];
    }
}
