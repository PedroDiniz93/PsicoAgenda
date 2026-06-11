<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('psychologists', function (Blueprint $table) {
            $table->string('whatsapp_sender_phone_id', 100)
                ->nullable()
                ->after('whatsapp_confirm_days_before')
                ->comment('Phone Number ID da Meta usado para enviar mensagens deste psicólogo');

            $table->string('whatsapp_sender_display_number', 30)
                ->nullable()
                ->after('whatsapp_sender_phone_id')
                ->comment('Número WhatsApp exibido para identificação interna');
        });
    }

    public function down(): void
    {
        Schema::table('psychologists', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_sender_phone_id',
                'whatsapp_sender_display_number',
            ]);
        });
    }
};
