<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gamekit_routines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('psychologist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->string('name', 160);
            $table->date('reference_date')->nullable();
            $table->string('status', 30)->default('draft');
            $table->unsignedInteger('current_version')->default(1);
            $table->timestamps();
            $table->index(['psychologist_id', 'patient_id', 'status']);
        });

        Schema::create('gamekit_routine_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gamekit_routine_id')->constrained('gamekit_routines')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->text('summary')->nullable();
            $table->timestamps();
            $table->unique(['gamekit_routine_id', 'version']);
        });

        Schema::create('gamekit_routine_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gamekit_routine_version_id')->constrained('gamekit_routine_versions')->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->time('start_time');
            $table->unsignedSmallInteger('duration_minutes');
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->string('category', 40);
            $table->string('icon', 50)->default('CircleCheck');
            $table->timestamps();
            $table->index(['gamekit_routine_version_id', 'position']);
        });

        Schema::create('gamekit_routine_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gamekit_routine_id')->constrained('gamekit_routines')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->string('status', 30)->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gamekit_routine_links');
        Schema::dropIfExists('gamekit_routine_blocks');
        Schema::dropIfExists('gamekit_routine_versions');
        Schema::dropIfExists('gamekit_routines');
    }
};
