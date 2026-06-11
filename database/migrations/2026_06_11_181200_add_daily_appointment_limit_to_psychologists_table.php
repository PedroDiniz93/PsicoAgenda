<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('psychologists', function (Blueprint $table) {
            $table->unsignedTinyInteger('daily_appointment_limit')
                ->nullable()
                ->after('session_duration')
                ->comment('Limite opcional de atendimentos por dia');
        });
    }

    public function down(): void
    {
        Schema::table('psychologists', function (Blueprint $table) {
            $table->dropColumn('daily_appointment_limit');
        });
    }
};
