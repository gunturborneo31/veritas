<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nomor WhatsApp (format 62…) yang diberi kabar saat pembayaran manual dikonfirmasi atau ditolak.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('notify_whatsapp', 20)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('notify_whatsapp');
        });
    }
};
