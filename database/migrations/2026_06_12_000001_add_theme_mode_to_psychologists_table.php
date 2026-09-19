<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('psychologists', function (Blueprint $table) {
            $table->string('theme_mode', 20)
                ->default('light')
                ->after('email_confirm_enabled')
                ->comment('Preferência de tema da interface para este psicólogo');
        });
    }

    public function down(): void
    {
        Schema::table('psychologists', function (Blueprint $table) {
            $table->dropColumn('theme_mode');
        });
    }
};
