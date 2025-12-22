<?php

namespace App\Console\Commands;

use App\Models\RecurringAppointment;
use App\Services\RecurringAppointmentService;
use Illuminate\Console\Command;

class SyncRecurringAppointments extends Command
{
    protected $signature = 'appointments:sync-recurring {--psychologist=}';

    protected $description = 'Gera ocorrências futuras para agendamentos recorrentes ativos.';

    public function __construct(
        private readonly RecurringAppointmentService $recurringAppointmentService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $psychologistId = $this->option('psychologist');

        $query = RecurringAppointment::query()
            ->where('status', 'active');

        if ($psychologistId) {
            $query->where('psychologist_id', $psychologistId);
        }

        $synced = 0;

        $query->chunkById(100, function ($recurrences) use (&$synced) {
            foreach ($recurrences as $recurrence) {
                $this->recurringAppointmentService->generateUpcomingOccurrences($recurrence);
                $synced++;
            }
        });

        $this->info(sprintf('Recorrências sincronizadas: %d', $synced));

        return self::SUCCESS;
    }
}
