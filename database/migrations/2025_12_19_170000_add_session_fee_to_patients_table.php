<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->enum('session_fee_type', ['session', 'biweekly', 'monthly'])
                ->default('session')
                ->after('status');
            $table->decimal('session_fee_value', 10, 2)
                ->nullable()
                ->after('session_fee_type');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn(['session_fee_type', 'session_fee_value']);
        });
    }
};
