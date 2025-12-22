<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('psychologists', function (Blueprint $table) {
            $table->boolean('whatsapp_confirm_enabled')
                ->default(false)
                ->after('google_calendar_token')
                ->comment('Se habilitado, envia confirmações automáticas via WhatsApp');

            $table->unsignedTinyInteger('whatsapp_confirm_days_before')
                ->default(1)
                ->after('whatsapp_confirm_enabled')
                ->comment('Dias antes do atendimento para enviar confirmação');
        });
    }

    public function down(): void
    {
        Schema::table('psychologists', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_confirm_enabled', 'whatsapp_confirm_days_before']);
        });
    }
};
