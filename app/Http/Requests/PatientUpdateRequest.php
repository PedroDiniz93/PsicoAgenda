<?php
// app/Http/Requests/PatientUpdateRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PatientUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'status' => ['sometimes', 'required', 'in:active,paused,closed'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'session_fee_type' => ['sometimes', 'nullable', 'in:session,biweekly,monthly'],
            'session_fee_value' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ];
    }
}
