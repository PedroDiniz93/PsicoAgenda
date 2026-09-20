<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gamekit_visual_activity_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('psychologist_id')->constrained()->cascadeOnDelete();
            $table->date('week_start');
            $table->unsignedTinyInteger('generated_count')->default(0);
            $table->timestamps();
            $table->unique(['psychologist_id', 'week_start']);
        });

        Schema::create('gamekit_visual_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('psychologist_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('style', 30);
            $table->string('theme', 160);
            $table->text('therapeutic_goal');
            $table->string('prompt_version', 30)->default('v1');
            $table->string('provider', 40)->nullable();
            $table->string('model', 80)->nullable();
            $table->string('storage_path')->nullable();
            $table->string('mime_type', 80)->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('status', 30)->default('generating');
            $table->text('failure_reason')->nullable();
            $table->string('public_token_hash', 64)->nullable()->unique();
            $table->timestamp('public_token_expires_at')->nullable();
            $table->string('public_status', 30)->nullable();
            $table->timestamps();
            $table->index(['psychologist_id', 'status', 'created_at'], 'visual_activities_owner_status_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gamekit_visual_activities');
        Schema::dropIfExists('gamekit_visual_activity_usage');
    }
};
