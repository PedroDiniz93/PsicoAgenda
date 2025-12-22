<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();

            $table->foreignId('psychologist_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            $table->enum('status', ['active', 'paused', 'closed'])
                ->default('active');

            $table->text('notes')->nullable();

            $table->timestamps();

            // índices úteis
            $table->index(['psychologist_id', 'name']);
            $table->index(['psychologist_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};

