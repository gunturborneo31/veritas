<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pembayaran QRIS dinamis (InterActive QRIS) dicatat otomatis tanpa bukti transfer,
     * jadi kolom bukti menjadi opsional untuk data lama.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('proof_disk')->nullable()->change();
            $table->string('proof_path')->nullable()->change();
            $table->string('provider_invoice_id')->nullable()->unique()->after('method');
            $table->text('qris_content')->nullable()->after('provider_invoice_id');
            $table->timestamp('requested_at')->nullable()->after('qris_content');
            $table->timestamp('expires_at')->nullable()->after('requested_at');
            $table->timestamp('paid_at')->nullable()->after('expires_at');
            $table->string('payer_name')->nullable()->after('paid_at');
            $table->string('payer_method')->nullable()->after('payer_name');
            $table->unsignedSmallInteger('status_checks')->default(0)->after('payer_method');
            $table->timestamp('last_checked_at')->nullable()->after('status_checks');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['provider_invoice_id']);
            $table->dropColumn(['provider_invoice_id', 'qris_content', 'requested_at', 'expires_at', 'paid_at', 'payer_name', 'payer_method', 'status_checks', 'last_checked_at']);
        });
    }
};
