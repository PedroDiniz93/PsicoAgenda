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
        Schema::create('recurring_appointments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('psychologist_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('patient_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('weekday');
            $table->time('start_time');
            $table->unsignedSmallInteger('session_duration')->default(50);
            $table->enum('type', ['online', 'in_person'])->default('online');
            $table->decimal('price', 10, 2)->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('timezone')->default(config('app.timezone'));
            $table->enum('status', ['active', 'paused', 'ended'])->default('active');
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['psychologist_id', 'weekday', 'start_time']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_appointments');
    }
};
