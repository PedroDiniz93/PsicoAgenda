<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('patient_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('psychologist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->dateTime('recorded_at')->useCurrent();
            $table->string('title');
            $table->text('notes');
            $table->timestamps();

            $table->index(['patient_id', 'recorded_at']);
            $table->index(['psychologist_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_records');
    }
};
