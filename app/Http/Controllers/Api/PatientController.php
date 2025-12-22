<?php
// app/Http/Controllers/Api/PatientController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PatientStoreRequest;
use App\Http\Requests\PatientUpdateRequest;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\PatientRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PatientController extends Controller
{
    private function psychologistId(Request $request): int
    {
        $user = $request->user()->loadMissing('psychologist');

        $psychologistId = $user->psychologist?->id;

        abort_if(
            !$psychologistId,
            403,
            'Usuário autenticado não possui um perfil de psicólogo.'
        );

        return (int) $psychologistId;
    }

    public function index(Request $request)
    {
        $psychologistId = $this->psychologistId($request);

        $query = Patient::query()
            ->where('psychologist_id', $psychologistId);

        if ($request->filled('status')) {
            $status = $request->string('status')->toString();
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        $perPage = (int) $request->integer('per_page', 10);
        $perPage = max(1, min($perPage, 1000));

        return response()->json(
            $query->orderByDesc('id')->paginate($perPage)
        );
    }

    public function store(PatientStoreRequest $request)
    {
        $psychologistId = $this->psychologistId($request);

        $patient = Patient::create([
            'psychologist_id' => $psychologistId,
            ...$request->validated(),
        ]);

        return response()->json($patient, 201);
    }

    public function show(Request $request, int $id)
    {
        $psychologistId = $this->psychologistId($request);

        $patient = Patient::where('psychologist_id', $psychologistId)
            ->findOrFail($id);

        return response()->json($patient);
    }

    public function update(PatientUpdateRequest $request, int $id)
    {
        $psychologistId = $this->psychologistId($request);

        $patient = Patient::where('psychologist_id', $psychologistId)
            ->findOrFail($id);

        $patient->update($request->validated());

        return response()->json($patient);
    }

    public function destroy(Request $request, int $id)
    {
        $psychologistId = $this->psychologistId($request);

        $patient = Patient::where('psychologist_id', $psychologistId)
            ->findOrFail($id);

        $patient->update(['status' => 'closed']);

        return response()->json(['ok' => true]);
    }

    public function export(Request $request, int $id): StreamedResponse
    {
        $psychologistId = $this->psychologistId($request);

        $patient = Patient::where('psychologist_id', $psychologistId)
            ->with([
                'appointments' => fn ($query) => $query->orderBy('start_at'),
                'records' => fn ($query) => $query->orderBy('recorded_at')->orderBy('id'),
            ])
            ->findOrFail($id);

        $rows = $this->buildPatientExportRows($patient);
        $fileName = sprintf('paciente-%d-%s.csv', $patient->id, now()->format('Ymd_His'));

        return $this->streamCsv($rows, $fileName);
    }

    public function bulkExport(Request $request): StreamedResponse
    {
        $psychologistId = $this->psychologistId($request);
        $ids = $request->input('patient_ids', []);

        abort_if(empty($ids), 422, 'Selecione pelo menos um paciente para exportar.');

        $patients = Patient::where('psychologist_id', $psychologistId)
            ->whereIn('id', $ids)
            ->with([
                'appointments' => fn ($query) => $query->orderBy('start_at'),
                'records' => fn ($query) => $query->orderBy('recorded_at')->orderBy('id'),
            ])
            ->get();

        abort_if($patients->isEmpty(), 404, 'Nenhum paciente encontrado para exportação.');

        $rows = [];

        foreach ($patients as $patient) {
            foreach ($this->buildPatientExportRows($patient) as $row) {
                $rows[] = $row;
            }
        }

        $fileName = sprintf('exportacao-pacientes-%s.csv', now()->format('Ymd_His'));

        return $this->streamCsv($rows, $fileName);
    }

    /**
     * @return array<int, array<string, string|int|float|null>>
     */
    private function buildPatientExportRows(Patient $patient): array
    {
        $exportedAt = now()->toIso8601String();
        $rows = [];

        $rows[] = $this->patientRow($patient, $exportedAt);

        foreach ($patient->appointments as $appointment) {
            $rows[] = $this->appointmentRow($patient, $appointment, $exportedAt);
        }

        foreach ($patient->records as $record) {
            $rows[] = $this->recordRow($patient, $record, $exportedAt);
        }

        return $rows;
    }

    private function patientColumns(Patient $patient, string $exportedAt): array
    {
        return [
            'paciente_nome' => $patient->name,
            'paciente_email' => $patient->email,
            'paciente_telefone' => $patient->phone,
            'paciente_status' => $this->translatePatientStatus($patient->status),
            'paciente_observacoes' => $patient->notes,
            'plano_tipo' => $this->translateSessionFeeType($patient->session_fee_type),
            'plano_valor' => $patient->session_fee_value,
        ];
    }

    private function patientRow(Patient $patient, string $exportedAt): array
    {
        return [
            'tipo_registro' => 'Paciente',
            ...$this->patientColumns($patient, $exportedAt),
            'atendimento_id' => '',
            'atendimento_inicio' => '',
            'atendimento_fim' => '',
            'atendimento_status' => '',
            'atendimento_modalidade' => '',
            'atendimento_valor' => '',
            'atendimento_link' => '',
            'atendimento_observacoes' => '',
            'registro_id' => '',
            'registro_titulo' => '',
            'registro_anotacoes' => '',
            'registro_data' => '',
        ];
    }

    private function appointmentRow(Patient $patient, Appointment $appointment, string $exportedAt): array
    {
        return [
            'tipo_registro' => 'Atendimento',
            ...$this->patientColumns($patient, $exportedAt),
            'atendimento_id' => $appointment->id,
            'atendimento_inicio' => $appointment->start_at?->toIso8601String(),
            'atendimento_fim' => $appointment->end_at?->toIso8601String(),
            'atendimento_status' => $this->translateAppointmentStatus($appointment->status),
            'atendimento_modalidade' => $this->translateAppointmentType($appointment->type),
            'atendimento_valor' => $appointment->price,
            'atendimento_link' => $appointment->meeting_url,
            'atendimento_observacoes' => $appointment->notes,
            'registro_id' => '',
            'registro_titulo' => '',
            'registro_anotacoes' => '',
            'registro_data' => '',
        ];
    }

    private function recordRow(Patient $patient, PatientRecord $record, string $exportedAt): array
    {
        return [
            'tipo_registro' => 'Prontuário',
            ...$this->patientColumns($patient, $exportedAt),
            'atendimento_id' => '',
            'atendimento_inicio' => '',
            'atendimento_fim' => '',
            'atendimento_status' => '',
            'atendimento_modalidade' => '',
            'atendimento_valor' => '',
            'atendimento_link' => '',
            'atendimento_observacoes' => '',
            'registro_id' => $record->id,
            'registro_titulo' => $record->title,
            'registro_anotacoes' => $record->notes,
            'registro_data' => $record->recorded_at?->toIso8601String(),
        ];
    }

    private function csvHeaders(): array
    {
        return [
            'tipo_registro',
            'paciente_nome',
            'paciente_email',
            'paciente_telefone',
            'paciente_status',
            'paciente_observacoes',
            'plano_tipo',
            'plano_valor',
            'atendimento_id',
            'atendimento_inicio',
            'atendimento_fim',
            'atendimento_status',
            'atendimento_modalidade',
            'atendimento_valor',
            'atendimento_link',
            'atendimento_observacoes',
            'registro_id',
            'registro_titulo',
            'registro_anotacoes',
            'registro_data',
        ];
    }

    private function translatePatientStatus(?string $status): string
    {
        return match ($status) {
            'active' => 'Ativo',
            'paused' => 'Pausado',
            'closed' => 'Encerrado',
            default => $status ?? 'Indefinido',
        };
    }

    private function translateSessionFeeType(?string $type): string
    {
        return match ($type) {
            'session' => 'Por sessão',
            'biweekly' => 'Quinzenal',
            'monthly' => 'Mensal',
            default => $type ?? 'Não informado',
        };
    }

    private function translateAppointmentStatus(?string $status): string
    {
        return match ($status) {
            'scheduled' => 'Agendado',
            'done' => 'Concluído',
            'missed' => 'Falta',
            'canceled' => 'Cancelado',
            default => $status ?? 'Indefinido',
        };
    }

    private function translateAppointmentType(?string $type): string
    {
        return match ($type) {
            'online' => 'Online',
            'in_person' => 'Presencial',
            default => $type ?? 'Não informado',
        };
    }

    private function streamCsv(array $rows, string $fileName): StreamedResponse
    {
        $headers = $this->csvHeaders();

        return Response::streamDownload(function () use ($rows, $headers) {
            $handle = fopen('php://output', 'w');

            // Excel-friendly BOM
            fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, $headers, ';');

            foreach ($rows as $row) {
                $line = [];
                foreach ($headers as $column) {
                    $line[] = $row[$column] ?? '';
                }

                fputcsv($handle, $line, ';');
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
