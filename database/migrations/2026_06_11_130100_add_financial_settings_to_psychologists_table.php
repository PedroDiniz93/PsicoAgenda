<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('psychologists', function (Blueprint $table) {
            $table->string('pix_key_type', 20)
                ->nullable()
                ->after('sms_confirm_enabled');
            $table->string('pix_key')
                ->nullable()
                ->after('pix_key_type');
            $table->string('default_payment_link', 2048)
                ->nullable()
                ->after('pix_key');
            $table->string('receipt_prefix', 20)
                ->default('REC')
                ->after('default_payment_link');
            $table->unsignedSmallInteger('payment_terms_days')
                ->default(0)
                ->after('receipt_prefix');
        });
    }

    public function down(): void
    {
        Schema::table('psychologists', function (Blueprint $table) {
            $table->dropColumn([
                'pix_key_type',
                'pix_key',
                'default_payment_link',
                'receipt_prefix',
                'payment_terms_days',
            ]);
        });
    }
};
