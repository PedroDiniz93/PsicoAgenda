<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('gamekit_sessions', function (Blueprint $table) {
            $table->foreignId('patient_id')->nullable()->after('psychologist_id')->constrained('patients')->nullOnDelete();
            $table->index(['psychologist_id', 'patient_id']);
        });
    }

    public function down(): void
    {
        Schema::table('gamekit_sessions', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->dropIndex(['psychologist_id', 'patient_id']);
            $table->dropColumn('patient_id');
        });
    }
};
