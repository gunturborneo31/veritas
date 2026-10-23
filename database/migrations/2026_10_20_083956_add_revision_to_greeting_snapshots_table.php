<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Snapshot tetap tidak bisa diubah, tetapi undangan boleh punya beberapa revisi:
     * setiap revisi menjadi baris baru, dan halaman tamu membaca revisi terakhir.
     */
    public function up(): void
    {
        Schema::table('greeting_snapshots', function (Blueprint $table) {
            $table->unsignedSmallInteger('revision')->default(1)->after('template_id');
            // Indeks gabungan dibuat lebih dulu agar foreign key greeting_id tetap punya indeks saat unik lama dilepas.
            $table->unique(['greeting_id', 'revision']);
            $table->dropUnique(['greeting_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('greeting_snapshots', function (Blueprint $table) {
            $table->unique('greeting_id');
            $table->dropUnique(['greeting_id', 'revision']);
            $table->dropColumn('revision');
        });
    }
};
