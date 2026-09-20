<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gamekit_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('psychologist_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('format')->default('association_memory');
            $table->string('age_group');
            $table->string('theme');
            $table->string('activity_type')->default('association');
            $table->string('difficulty');
            $table->unsignedSmallInteger('duration_minutes')->default(45);
            $table->string('status')->default('draft');
            $table->unsignedInteger('current_version')->default(1);
            $table->timestamps();
            $table->index(['psychologist_id', 'status']);
        });

        Schema::create('gamekit_template_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gamekit_template_id')->constrained('gamekit_templates')->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->unsignedSmallInteger('position');
            $table->text('context');
            $table->text('question');
            $table->json('options');
            $table->timestamps();
            $table->index(['gamekit_template_id', 'version', 'position'], 'gkt_cards_version_position_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gamekit_template_cards');
        Schema::dropIfExists('gamekit_templates');
    }
};
