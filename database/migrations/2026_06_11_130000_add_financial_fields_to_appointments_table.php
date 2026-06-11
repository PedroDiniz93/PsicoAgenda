<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->date('payment_due_at')
                ->nullable()
                ->after('paid_at');
            $table->string('payment_method', 40)
                ->nullable()
                ->after('payment_due_at');
            $table->string('payment_link', 2048)
                ->nullable()
                ->after('payment_method');
            $table->string('receipt_number', 80)
                ->nullable()
                ->after('payment_link');
            $table->timestamp('receipt_issued_at')
                ->nullable()
                ->after('receipt_number');
            $table->text('payment_notes')
                ->nullable()
                ->after('receipt_issued_at');

            $table->index(['psychologist_id', 'payment_due_at'], 'appointments_psychologist_payment_due_index');
            $table->unique(['psychologist_id', 'receipt_number'], 'appointments_psychologist_receipt_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropUnique('appointments_psychologist_receipt_number_unique');
            $table->dropIndex('appointments_psychologist_payment_due_index');
            $table->dropColumn([
                'payment_due_at',
                'payment_method',
                'payment_link',
                'receipt_number',
                'receipt_issued_at',
                'payment_notes',
            ]);
        });
    }
};
