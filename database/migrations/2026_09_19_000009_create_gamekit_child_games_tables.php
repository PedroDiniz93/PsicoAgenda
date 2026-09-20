<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gamekit_tictactoe_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('psychologist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->string('mode', 30)->default('computer');
            $table->string('status', 30)->default('draft');
            $table->string('result', 30)->nullable();
            $table->json('moves')->nullable();
            $table->string('public_token_hash', 64)->nullable()->unique();
            $table->timestamp('public_token_expires_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->index(['psychologist_id', 'status']);
        });

        Schema::create('gamekit_hangman_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('psychologist_id')->constrained()->cascadeOnDelete();
            $table->string('name', 160);
            $table->string('age_group', 80);
            $table->string('theme', 120);
            $table->unsignedSmallInteger('word_count');
            $table->json('words');
            $table->string('status', 30)->default('saved');
            $table->timestamps();
            $table->index(['psychologist_id', 'theme']);
        });

        Schema::create('gamekit_hangman_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gamekit_hangman_game_id')->constrained('gamekit_hangman_games')->cascadeOnDelete();
            $table->foreignId('psychologist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->string('status', 30)->default('draft');
            $table->json('result')->nullable();
            $table->string('public_token_hash', 64)->nullable()->unique();
            $table->timestamp('public_token_expires_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->index(['psychologist_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gamekit_hangman_sessions');
        Schema::dropIfExists('gamekit_hangman_games');
        Schema::dropIfExists('gamekit_tictactoe_sessions');
    }
};
