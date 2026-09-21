<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('online_sessions', 'patient_entry_approved_at')) {
            Schema::table('online_sessions', function (Blueprint $table) {
                $table->dateTime('patient_entry_approved_at')->nullable()->after('patient_connection_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('online_sessions', 'patient_entry_approved_at')) {
            Schema::table('online_sessions', function (Blueprint $table) {
                $table->dropColumn('patient_entry_approved_at');
            });
        }
    }
};
