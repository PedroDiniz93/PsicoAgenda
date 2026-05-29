<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('cpf', 14)->nullable()->after('email');
            $table->date('birth_date')->nullable()->after('cpf');
            $table->json('emergency_contacts')->nullable()->after('birth_date');
            $table->string('minor_guardian_name')->nullable()->after('emergency_contacts');
            $table->string('minor_guardian_phone', 50)->nullable()->after('minor_guardian_name');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn([
                'cpf',
                'birth_date',
                'emergency_contacts',
                'minor_guardian_name',
                'minor_guardian_phone',
            ]);
        });
    }
};
