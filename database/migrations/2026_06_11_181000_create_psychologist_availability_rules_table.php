<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('psychologist_availability_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('psychologist_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['psychologist_id', 'weekday', 'is_active'], 'psych_availability_weekday_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('psychologist_availability_rules');
    }
};
