<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PatientRecordStoreRequest;
use App\Http\Requests\PatientRecordUpdateRequest;
use App\Models\Patient;
use App\Models\PatientRecord;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PatientRecordController extends Controller
{
    private const HOMEWORK_STATUSES = ['pending', 'in_progress', 'done'];

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

        $query = PatientRecord::where('patient_id', $patient->id)
            ->where('psychologist_id', $patient->psychologist_id);

        if ($from = $this->parseFilterDate($request, 'from')) {
            $query->whereDate('recorded_at', '>=', $from);
        }

        if ($to = $this->parseFilterDate($request, 'to')) {
            $query->whereDate('recorded_at', '<=', $to);
        }

        if ($request->filled('objective')) {
            $query->whereJsonContains('treatment_objectives', $request->string('objective')->toString());
        }

        if ($request->filled('technique')) {
            $query->whereJsonContains('techniques', $request->string('technique')->toString());
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($inner) use ($search) {
                $inner->where('title', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $records = $query
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

        $data = $this->prepareTherapeuticPayload($data);

        $data['attachments'] = $this->storeUploadedAttachments(
            $request->file('attachments', []),
            $patient
        );

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

        $existingIds = Arr::wrap($data['existing_attachments'] ?? []);
        unset($data['existing_attachments']);

        $data = $this->prepareTherapeuticPayload($data);

        $keptAttachments = $this->retainExistingAttachments($record, $existingIds);
        $newAttachments = $this->storeUploadedAttachments(
            $request->file('attachments', []),
            $patient
        );

        $data['attachments'] = array_values([...$keptAttachments, ...$newAttachments]);

        $record->update($data);

        return response()->json($record);
    }

    public function destroy(Request $request, int $patientId, int $recordId)
    {
        $patient = $this->resolvePatient($request, $patientId);
        $record = $this->resolveRecord($patient, $recordId);
        $this->deleteAttachmentsCollection($record->attachments ?? []);
        $record->delete();

        return response()->json(['deleted' => true]);
    }

    private function prepareTherapeuticPayload(array $data): array
    {
        $data['notes'] = $data['notes'] ?? '';
        $data['treatment_objectives'] = $this->cleanStringArray($data['treatment_objectives'] ?? []);
        $data['techniques'] = $this->cleanStringArray($data['techniques'] ?? []);
        $data['homework_items'] = $this->sanitizeHomeworkItems($data['homework_items'] ?? []);

        unset($data['existing_attachments']);

        return $data;
    }

    private function cleanStringArray(?array $items): array
    {
        if (!$items) {
            return [];
        }

        $clean = array_map(static fn ($value) => trim((string) $value), $items);

        return array_values(array_filter($clean, static fn ($value) => $value !== ''));
    }

    private function sanitizeHomeworkItems(?array $items): array
    {
        if (!$items) {
            return [];
        }

        $sanitized = [];

        foreach ($items as $item) {
            $description = trim((string) ($item['description'] ?? ''));
            if ($description === '') {
                continue;
            }

            $status = in_array($item['status'] ?? 'pending', self::HOMEWORK_STATUSES, true)
                ? $item['status']
                : 'pending';

            $sanitized[] = [
                'id' => $item['id'] ?? (string) Str::uuid(),
                'description' => $description,
                'status' => $status,
            ];
        }

        return $sanitized;
    }

    /**
     * @param array<int, UploadedFile>|UploadedFile[] $files
     */
    private function storeUploadedAttachments(array $files, Patient $patient): array
    {
        $saved = [];

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store("patient-records/{$patient->id}", 'public');
            $saved[] = [
                'id' => (string) Str::uuid(),
                'name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
                'uploaded_at' => now()->toIso8601String(),
            ];
        }

        return $saved;
    }

    private function retainExistingAttachments(PatientRecord $record, array $keepIds): array
    {
        $attachments = $record->attachments ?? [];
        if (!$attachments) {
            return [];
        }

        $kept = [];

        foreach ($attachments as $attachment) {
            $id = $attachment['id'] ?? null;
            if (!$id || !in_array($id, $keepIds, true)) {
                $this->deleteStoredAttachment($attachment);
                continue;
            }

            $kept[] = $attachment;
        }

        return $kept;
    }

    private function deleteStoredAttachment(array $attachment): void
    {
        $path = $attachment['path'] ?? null;
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }

    private function deleteAttachmentsCollection(array $attachments): void
    {
        foreach ($attachments as $attachment) {
            if (is_array($attachment)) {
                $this->deleteStoredAttachment($attachment);
            }
        }
    }

    private function parseFilterDate(Request $request, string $key): ?string
    {
        if (!$request->filled($key)) {
            return null;
        }

        try {
            return Carbon::parse($request->input($key))->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
