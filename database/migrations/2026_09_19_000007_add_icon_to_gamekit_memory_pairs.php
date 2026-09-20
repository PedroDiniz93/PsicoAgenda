<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gamekit_memory_pairs', function (Blueprint $table) {
            $table->string('icon', 50)->default('Sparkles')->after('feedback');
        });
    }

    public function down(): void
    {
        Schema::table('gamekit_memory_pairs', function (Blueprint $table) {
            $table->dropColumn('icon');
        });
    }
};
