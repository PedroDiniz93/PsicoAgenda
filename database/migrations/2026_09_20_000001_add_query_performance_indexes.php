<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table): void {
            $table->index(['psychologist_id', 'status', 'id'], 'patients_psychologist_status_id_index');
        });

        Schema::table('patient_alerts', function (Blueprint $table): void {
            $table->index(['psychologist_id', 'type', 'resolved_at'], 'patient_alerts_owner_type_resolved_index');
        });

        Schema::table('appointments', function (Blueprint $table): void {
            $table->index(['psychologist_id', 'receipt_issued_at'], 'appointments_owner_receipt_issued_index');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropIndex('appointments_owner_receipt_issued_index');
        });

        Schema::table('patient_alerts', function (Blueprint $table): void {
            $table->dropIndex('patient_alerts_owner_type_resolved_index');
        });

        Schema::table('patients', function (Blueprint $table): void {
            $table->dropIndex('patients_psychologist_status_id_index');
        });
    }
};
