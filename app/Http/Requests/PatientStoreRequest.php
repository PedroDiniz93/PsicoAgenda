<?php
// app/Http/Requests/PatientStoreRequest.php
namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PatientStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'cpf' => ['nullable', 'string', 'max:14'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'emergency_contacts' => ['nullable', 'array', 'max:3'],
            'emergency_contacts.*.name' => ['required_with:emergency_contacts.*.phone,emergency_contacts.*.relationship', 'nullable', 'string', 'max:255'],
            'emergency_contacts.*.phone' => ['required_with:emergency_contacts.*.name,emergency_contacts.*.relationship', 'nullable', 'string', 'max:50'],
            'emergency_contacts.*.relationship' => ['nullable', 'string', 'max:100'],
            'minor_guardian_name' => ['nullable', 'string', 'max:255'],
            'minor_guardian_phone' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'in:active,paused,closed'],
            'notes' => ['nullable', 'string'],
            'session_fee_type' => ['nullable', 'in:session,biweekly,monthly'],
            'session_fee_value' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (!$this->isMinorPatient()) {
                return;
            }

            if (!$this->filled('minor_guardian_name')) {
                $validator->errors()->add('minor_guardian_name', 'Informe o nome do responsável pelo menor de idade.');
            }

            if (!$this->filled('minor_guardian_phone')) {
                $validator->errors()->add('minor_guardian_phone', 'Informe o telefone do responsável pelo menor de idade.');
            }
        });
    }

    private function isMinorPatient(): bool
    {
        if (!$this->filled('birth_date')) {
            return false;
        }

        try {
            return Carbon::parse($this->input('birth_date'))->age < 18;
        } catch (\Throwable) {
            return false;
        }
    }
}
