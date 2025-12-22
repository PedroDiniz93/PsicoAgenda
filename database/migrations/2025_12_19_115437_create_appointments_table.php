<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('psychologist_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('patient_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->dateTime('start_at');
            $table->dateTime('end_at');

            $table->enum('status', [
                'scheduled',
                'done',
                'missed',
                'canceled',
            ])->default('scheduled');

            $table->enum('type', [
                'online',
                'in_person',
            ])->default('online');

            $table->decimal('price', 10, 2)->nullable();
            $table->dateTime('paid_at')->nullable();

            $table->timestamps();

            $table->index(['psychologist_id', 'start_at']);
            $table->index(['patient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};

