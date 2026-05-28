<?php
// app/Http/Controllers/Api/PatientController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PatientStoreRequest;
use App\Http\Requests\PatientUpdateRequest;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\PatientAlert;
use App\Models\PatientRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class PatientController extends Controller
{
    private const EXPORT_TYPES = [
        'patients' => [
            'filename' => 'pacientes.csv',
        ],
        'appointments' => [
            'filename' => 'agendamentos.csv',
        ],
        'records' => [
            'filename' => 'prontuarios.csv',
        ],
    ];

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

    public function inactivityAlerts(Request $request)
    {
        $psychologistId = $this->psychologistId($request);

        $alerts = PatientAlert::query()
            ->with(['patient' => function ($query) {
                $query->select('id', 'name', 'status', 'email', 'phone');
            }])
            ->where('psychologist_id', $psychologistId)
            ->where('type', 'patient-inactivity')
            ->orderByDesc('triggered_at')
            ->get();

        $patients = $alerts->map(function (PatientAlert $alert) {
            $payload = $alert->payload ?? [];
            $daysSince = $payload['days_since_last_appointment'] ?? null;

            if (is_numeric($daysSince)) {
                $daysSince = (int) round((float) $daysSince);
            } else {
                $daysSince = null;
            }

            return [
                'id' => $alert->patient_id,
                'name' => $alert->patient?->name,
                'status' => $alert->patient?->status,
                'email' => $alert->patient?->email,
                'phone' => $alert->patient?->phone,
                'last_appointment_at' => $payload['last_appointment_at'] ?? null,
                'reference_date' => $payload['reference_date'] ?? null,
                'days_since_last_appointment' => $daysSince,
            ];
        });

        $threshold = (int) ($alerts->first()?->payload['threshold_days'] ?? 15);

        $hasUnread = $alerts->contains(function (PatientAlert $alert) {
            return $alert->resolved_at === null;
        });

        return response()->json([
            'threshold_days' => $threshold,
            'count' => $patients->count(),
            'patients' => $patients,
            'has_unread' => $hasUnread,
        ]);
    }

    public function acknowledgeInactivityAlerts(Request $request)
    {
        $psychologistId = $this->psychologistId($request);

        $acknowledged = PatientAlert::query()
            ->where('psychologist_id', $psychologistId)
            ->where('type', 'patient-inactivity')
            ->whereNull('resolved_at')
            ->update([
                'resolved_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'acknowledged' => (int) $acknowledged,
        ]);
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
        $types = $this->resolveExportTypes($request);

        abort_if(empty($ids), 422, 'Selecione pelo menos um paciente para exportar.');

        $patients = Patient::where('psychologist_id', $psychologistId)
            ->whereIn('id', $ids)
            ->with([
                'appointments' => fn ($query) => $query->orderBy('start_at'),
                'records' => fn ($query) => $query->orderBy('recorded_at')->orderBy('id'),
            ])
            ->get();

        abort_if($patients->isEmpty(), 404, 'Nenhum paciente encontrado para exportação.');

        $rowsByType = [];
        foreach ($types as $type) {
            $rowsByType[$type] = [];
        }

        foreach ($patients as $patient) {
            $patientRows = $this->buildPatientExportRowsByType($patient);

            foreach ($types as $type) {
                foreach ($patientRows[$type] ?? [] as $row) {
                    $rowsByType[$type][] = $row;
                }
            }
        }

        $files = [];
        foreach ($types as $type) {
            $files[$this->exportTypeFileName($type)] = $this->renderCsvContent(
                $rowsByType[$type],
                $this->csvHeadersForType($type)
            );
        }

        $fileName = sprintf('exportacao-pacientes-%s.zip', now()->format('Ymd_His'));

        return $this->streamZip($files, $fileName);
    }

    /**
     * @return array<int, array<string, string|int|float|null>>
     */
    private function buildPatientExportRows(Patient $patient): array
    {
        $groupedRows = $this->buildPatientExportRowsByType($patient);
        $rows = [];

        foreach ($groupedRows as $group) {
            foreach ($group as $row) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @return array<string, array<int, array<string, string|int|float|null>>>
     */
    private function buildPatientExportRowsByType(Patient $patient): array
    {
        $exportedAt = now()->toIso8601String();

        $rows = [
            'patients' => [
                $this->patientRow($patient, $exportedAt),
            ],
            'appointments' => [],
            'records' => [],
        ];

        foreach ($patient->appointments as $appointment) {
            $rows['appointments'][] = $this->appointmentRow($patient, $appointment, $exportedAt);
        }

        foreach ($patient->records as $record) {
            $rows['records'][] = $this->recordRow($patient, $record, $exportedAt);
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
            'registro_objetivos' => implode(' | ', $record->treatment_objectives ?? []),
            'registro_tecnicas' => implode(' | ', $record->techniques ?? []),
            'registro_tarefas' => collect($record->homework_items ?? [])
                ->map(function ($item) {
                    if (!is_array($item)) {
                        return null;
                    }
                    $status = match ($item['status'] ?? '') {
                        'done' => 'Concluída',
                        'in_progress' => 'Em andamento',
                        default => 'Pendente',
                    };
                    return "{$status}: {$item['description']}";
                })
                ->filter()
                ->implode(' | '),
            'registro_anexos' => collect($record->attachments ?? [])
                ->map(fn ($attachment) => is_array($attachment) ? ($attachment['url'] ?? $attachment['name'] ?? '') : '')
                ->filter()
                ->implode(' | '),
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
            'registro_objetivos',
            'registro_tecnicas',
            'registro_tarefas',
            'registro_anexos',
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
        return Response::streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            $this->writeCsvToHandle($handle, $rows);
            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function renderCsvContent(array $rows, ?array $headers = null): string
    {
        $handle = fopen('php://temp', 'r+');
        $this->writeCsvToHandle($handle, $rows, $headers);
        rewind($handle);
        $content = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $content;
    }

    private function writeCsvToHandle($handle, array $rows, ?array $headers = null): void
    {
        $headers = $headers ?? $this->csvHeaders();

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
    }

    private function streamZip(array $files, string $fileName): StreamedResponse
    {
        return Response::streamDownload(function () use ($files) {
            $temporaryPath = tempnam(sys_get_temp_dir(), 'psicoagenda-export-');

            if ($temporaryPath === false) {
                throw new \RuntimeException('Não foi possível preparar o arquivo para exportação.');
            }

            $zip = new ZipArchive();
            $status = $zip->open($temporaryPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

            if ($status !== true) {
                throw new \RuntimeException('Não foi possível gerar o arquivo compactado de exportação.');
            }

            foreach ($files as $name => $content) {
                $zip->addFromString($name, $content);
            }

            $zip->close();

            $stream = fopen($temporaryPath, 'rb');

            if ($stream === false) {
                @unlink($temporaryPath);
                throw new \RuntimeException('Não foi possível transmitir o arquivo de exportação.');
            }

            fpassthru($stream);
            fclose($stream);
            @unlink($temporaryPath);
        }, $fileName, [
            'Content-Type' => 'application/zip',
        ]);
    }

    private function resolveExportTypes(Request $request): array
    {
        $availableTypes = array_keys(self::EXPORT_TYPES);

        if (!$request->has('types')) {
            return $availableTypes;
        }

        $incoming = $request->input('types');
        $incoming = is_array($incoming) ? $incoming : [];

        $selected = [];
        foreach ($incoming as $type) {
            $type = strtolower((string) $type);

            if (in_array($type, $availableTypes, true) && !in_array($type, $selected, true)) {
                $selected[] = $type;
            }
        }

        abort_if(empty($selected), 422, 'Selecione pelo menos um tipo de informação para exportar.');

        return $selected;
    }

    private function exportTypeFileName(string $type): string
    {
        return self::EXPORT_TYPES[$type]['filename'] ?? sprintf('%s.csv', $type);
    }

    private function csvHeadersForType(string $type): array
    {
        $patientColumns = [
            'paciente_nome',
            'paciente_email',
            'paciente_telefone',
            'paciente_status',
            'paciente_observacoes',
            'plano_tipo',
            'plano_valor',
        ];

        return match ($type) {
            'patients' => array_merge(['tipo_registro'], $patientColumns),
            'appointments' => array_merge(
                ['tipo_registro'],
                $patientColumns,
                [
                    'atendimento_id',
                    'atendimento_inicio',
                    'atendimento_fim',
                    'atendimento_status',
                    'atendimento_modalidade',
                    'atendimento_valor',
                    'atendimento_link',
                    'atendimento_observacoes',
                ]
            ),
            'records' => array_merge(
                ['tipo_registro'],
                $patientColumns,
                [
                    'registro_id',
                    'registro_titulo',
                    'registro_anotacoes',
                    'registro_data',
                    'registro_objetivos',
                    'registro_tecnicas',
                    'registro_tarefas',
                    'registro_anexos',
                ]
            ),
            default => $this->csvHeaders(),
        };
    }
}
