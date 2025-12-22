<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PatientRecordStoreRequest;
use App\Http\Requests\PatientRecordUpdateRequest;
use App\Models\Patient;
use App\Models\PatientRecord;
use Illuminate\Http\Request;

class PatientRecordController extends Controller
{
    private function psychologistId(Request $request): int
    {
        $user = $request->user()->loadMissing('psychologist');
        $psychologistId = $user->psychologist?->id;

        abort_if(!$psychologistId, 403, 'Usuário autenticado não possui um perfil de psicólogo.');

        return (int) $psychologistId;
    }

    private function resolvePatient(Request $request, int $patientId): Patient
    {
        $psychologistId = $this->psychologistId($request);

        return Patient::where('psychologist_id', $psychologistId)
            ->findOrFail($patientId);
    }

    private function resolveRecord(Patient $patient, int $recordId): PatientRecord
    {
        return PatientRecord::where('patient_id', $patient->id)
            ->where('psychologist_id', $patient->psychologist_id)
            ->findOrFail($recordId);
    }

    public function index(Request $request, int $patientId)
    {
        $patient = $this->resolvePatient($request, $patientId);

        $records = PatientRecord::where('patient_id', $patient->id)
            ->where('psychologist_id', $patient->psychologist_id)
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->paginate(10);

        return response()->json($records);
    }

    public function store(PatientRecordStoreRequest $request, int $patientId)
    {
        $patient = $this->resolvePatient($request, $patientId);

        $data = $request->validated();
        $data['recorded_at'] = $data['recorded_at'] ?? now();

        $record = PatientRecord::create([
            'psychologist_id' => $patient->psychologist_id,
            'patient_id' => $patient->id,
            ...$data,
        ]);

        return response()->json($record, 201);
    }

    public function update(PatientRecordUpdateRequest $request, int $patientId, int $recordId)
    {
        $patient = $this->resolvePatient($request, $patientId);
        $record = $this->resolveRecord($patient, $recordId);

        $data = $request->validated();
        if (array_key_exists('recorded_at', $data) && !$data['recorded_at']) {
            $data['recorded_at'] = $record->recorded_at;
        }

        $record->update($data);

        return response()->json($record);
    }

    public function destroy(Request $request, int $patientId, int $recordId)
    {
        $patient = $this->resolvePatient($request, $patientId);
        $record = $this->resolveRecord($patient, $recordId);
        $record->delete();

        return response()->json(['deleted' => true]);
    }
}
