<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Balasan penerima boleh disertai satu foto atau video; teks jadi opsional bila ada lampiran.
     */
    public function up(): void
    {
        Schema::table('replies', function (Blueprint $table) {
            $table->text('message')->nullable()->change();
            $table->string('attachment_type', 10)->nullable()->after('message');
            $table->string('attachment_path')->nullable()->after('attachment_type');
        });
    }

    public function down(): void
    {
        Schema::table('replies', function (Blueprint $table) {
            $table->dropColumn(['attachment_type', 'attachment_path']);
        });
    }
};
