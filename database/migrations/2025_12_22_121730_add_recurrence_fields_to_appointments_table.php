<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('recurrence_id')
                ->nullable()
                ->after('patient_id')
                ->constrained('recurring_appointments')
                ->nullOnDelete();

            $table->date('occurrence_date')
                ->nullable()
                ->after('end_at');

            $table->index(['recurrence_id', 'occurrence_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('appointments_recurrence_id_occurrence_date_index');
            $table->dropConstrainedForeignId('recurrence_id');
            $table->dropColumn(['occurrence_date']);
        });
    }
};
