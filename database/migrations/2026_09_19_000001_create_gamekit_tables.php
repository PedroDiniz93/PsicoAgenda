<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('gamekit_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('psychologist_id')->constrained()->cascadeOnDelete();
            $table->string('format')->default('association_memory');
            $table->string('age_group');
            $table->string('theme');
            $table->string('difficulty');
            $table->unsignedSmallInteger('duration_minutes')->default(45);
            $table->string('status')->default('draft');
            $table->string('public_token_hash', 64)->nullable()->unique();
            $table->timestamp('public_token_expires_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->index(['psychologist_id', 'status']);
        });

        Schema::create('gamekit_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gamekit_session_id')->constrained('gamekit_sessions')->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('pair_key');
            $table->text('prompt_a');
            $table->text('prompt_b');
            $table->json('options')->nullable();
            $table->timestamps();
            $table->index(['gamekit_session_id', 'position']);
        });

        Schema::create('gamekit_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gamekit_session_id')->constrained('gamekit_sessions')->cascadeOnDelete();
            $table->foreignId('gamekit_card_id')->constrained('gamekit_cards')->cascadeOnDelete();
            $table->string('participant_key', 64);
            $table->text('answer');
            $table->boolean('reviewed')->default(false);
            $table->boolean('include_in_record')->default(false);
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['gamekit_card_id', 'participant_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gamekit_responses');
        Schema::dropIfExists('gamekit_cards');
        Schema::dropIfExists('gamekit_sessions');
    }
};
