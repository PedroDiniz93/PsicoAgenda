<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('meeting_url', 2048)
                ->nullable()
                ->after('sms_reminder_sent_at');
            $table->string('meeting_provider', 100)
                ->nullable()
                ->after('meeting_url');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['meeting_url', 'meeting_provider']);
        });
    }
};
