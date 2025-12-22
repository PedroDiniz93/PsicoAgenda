<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('psychologists', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            $table->string('timezone')
                ->default('America/Sao_Paulo');

            $table->unsignedInteger('session_duration')
                ->default(50)
                ->comment('Session duration in minutes');

            $table->boolean('allow_online')->default(true);
            $table->boolean('allow_in_person')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('psychologists');
    }
};
