<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('psychologist_schedule_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('psychologist_id')->constrained()->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->enum('type', ['block', 'vacation'])->default('block');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['psychologist_id', 'starts_at', 'ends_at'], 'psych_schedule_blocks_range_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('psychologist_schedule_blocks');
    }
};
