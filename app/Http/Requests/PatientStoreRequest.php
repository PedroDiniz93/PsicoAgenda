<?php
// app/Http/Requests/PatientStoreRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PatientStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['nullable', 'in:active,paused,closed'],
            'notes' => ['nullable', 'string'],
            'session_fee_type' => ['nullable', 'in:session,biweekly,monthly'],
            'session_fee_value' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
