<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('psychologists', function (Blueprint $table) {
            $table->boolean('email_confirm_enabled')
                ->default(false)
                ->after('whatsapp_confirm_days_before');

            $table->boolean('sms_confirm_enabled')
                ->default(false)
                ->after('email_confirm_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('psychologists', function (Blueprint $table) {
            $table->dropColumn(['email_confirm_enabled', 'sms_confirm_enabled']);
        });
    }
};
