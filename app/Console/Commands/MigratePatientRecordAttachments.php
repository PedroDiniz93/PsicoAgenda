<?php

namespace App\Console\Commands;

use App\Models\PatientRecord;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigratePatientRecordAttachments extends Command
{
    protected $signature = 'patient-records:migrate-private {--dry-run : Apenas mostra o que seria migrado}';

    protected $description = 'Move anexos antigos de prontuários para o disco privado';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $migrated = 0;
        $missing = 0;

        PatientRecord::query()->whereNotNull('attachments')->chunkById(100, function ($records) use ($dryRun, &$migrated, &$missing): void {
            foreach ($records as $record) {
                $attachments = $record->attachments ?? [];
                $changed = false;

                foreach ($attachments as &$attachment) {
                    if (! is_array($attachment) || ($attachment['disk'] ?? null) === 'private') {
                        continue;
                    }

                    $path = $attachment['path'] ?? null;
                    if (! $path || ! Storage::disk('public')->exists($path)) {
                        $missing++;

                        continue;
                    }

                    $this->line(($dryRun ? '[dry-run] ' : '')."{$record->id}: {$path}");
                    $migrated++;

                    if (! $dryRun) {
                        Storage::disk('private')->put($path, Storage::disk('public')->get($path));
                        Storage::disk('public')->delete($path);
                        $attachment['disk'] = 'private';
                        $changed = true;
                    }
                }
                unset($attachment);

                if ($changed) {
                    $record->update(['attachments' => $attachments]);
                }
            }
        });

        $this->info(($dryRun ? 'Seriam migrados' : 'Migrados').": {$migrated} anexo(s). Missing: {$missing}.");

        return self::SUCCESS;
    }
}
