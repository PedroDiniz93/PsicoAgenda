<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('psychologists', function (Blueprint $table) {
            $table->text('google_calendar_token')
                ->nullable()
                ->after('allow_in_person')
                ->comment('Credenciais do Google Calendar criptografadas');
        });
    }

    public function down(): void
    {
        Schema::table('psychologists', function (Blueprint $table) {
            $table->dropColumn('google_calendar_token');
        });
    }
};
