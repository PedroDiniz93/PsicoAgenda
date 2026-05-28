<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('patient_records', function (Blueprint $table) {
            $table->json('treatment_objectives')->nullable()->after('notes');
            $table->json('techniques')->nullable()->after('treatment_objectives');
            $table->json('homework_items')->nullable()->after('techniques');
            $table->json('attachments')->nullable()->after('homework_items');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patient_records', function (Blueprint $table) {
            $table->dropColumn(['treatment_objectives', 'techniques', 'homework_items', 'attachments']);
        });
    }
};
