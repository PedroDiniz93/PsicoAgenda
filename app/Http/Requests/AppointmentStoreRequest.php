<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AppointmentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'status' => ['nullable', 'in:scheduled,done,missed,canceled'],
            'type' => ['nullable', 'in:online,in_person'],
            'price' => ['nullable', 'numeric'],
            'paid_at' => ['nullable', 'date'],
            'repeat_weekly' => ['sometimes', 'boolean'],
            'repeat_until' => ['nullable', 'date'],
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
            'repeat_until.date' => 'A data final da recorrência é inválida.',
        ];
    }
}
