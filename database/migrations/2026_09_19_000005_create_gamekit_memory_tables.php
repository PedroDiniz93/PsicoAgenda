<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('gamekit_memory_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('psychologist_id')->constrained()->cascadeOnDelete();
            $table->string('name', 160);
            $table->string('age_group', 80);
            $table->string('theme', 120);
            $table->string('difficulty', 40);
            $table->unsignedSmallInteger('pair_count');
            $table->string('status', 30)->default('draft');
            $table->timestamps();
            $table->index(['psychologist_id', 'status']);
        });

        Schema::create('gamekit_memory_pairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gamekit_memory_game_id')->constrained('gamekit_memory_games')->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('label_a', 160);
            $table->string('label_b', 160);
            $table->string('concept', 160);
            $table->text('feedback');
            $table->string('accent', 30)->default('sage');
            $table->timestamps();
            $table->index(['gamekit_memory_game_id', 'position']);
        });

        Schema::create('gamekit_memory_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gamekit_memory_game_id')->constrained('gamekit_memory_games')->cascadeOnDelete();
            $table->foreignId('psychologist_id')->constrained()->cascadeOnDelete();
            $table->string('public_token_hash', 64)->nullable()->unique();
            $table->timestamp('public_token_expires_at')->nullable();
            $table->string('status', 30)->default('draft');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->index(['psychologist_id', 'status']);
        });

        Schema::create('gamekit_memory_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gamekit_memory_session_id')->unique()->constrained('gamekit_memory_sessions')->cascadeOnDelete();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedSmallInteger('matched_pairs')->default(0);
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->boolean('completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gamekit_memory_results');
        Schema::dropIfExists('gamekit_memory_sessions');
        Schema::dropIfExists('gamekit_memory_pairs');
        Schema::dropIfExists('gamekit_memory_games');
    }
};
