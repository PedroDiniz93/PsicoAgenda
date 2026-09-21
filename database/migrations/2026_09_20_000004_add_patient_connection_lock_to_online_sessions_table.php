<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('online_sessions', 'patient_connection_id')) {
            Schema::table('online_sessions', function (Blueprint $table) {
                $table->string('patient_connection_id', 100)->nullable()->after('status');
                $table->dateTime('patient_connection_at')->nullable()->after('patient_connection_id');
            });
        }

        $indexNames = collect(Schema::getIndexes('online_sessions'))->pluck('name');
        if (! $indexNames->contains('online_session_patient_conn_idx')) {
            Schema::table('online_sessions', function (Blueprint $table) {
                $table->index(['patient_connection_id', 'patient_connection_at'], 'online_session_patient_conn_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::table('online_sessions', function (Blueprint $table) {
            $table->dropIndex('online_session_patient_conn_idx');
            $table->dropColumn(['patient_connection_id', 'patient_connection_at']);
        });
    }
};
