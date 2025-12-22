<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->timestamp('email_reminder_sent_at')->nullable()->after('confirmation_channel');
            $table->timestamp('sms_reminder_sent_at')->nullable()->after('email_reminder_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['email_reminder_sent_at', 'sms_reminder_sent_at']);
        });
    }
};
