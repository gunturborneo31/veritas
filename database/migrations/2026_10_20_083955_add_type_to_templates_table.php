<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Template menentukan jenis produk draf yang dibuat darinya (ucapan atau undangan).
     */
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->string('type')->default('ucapan')->after('category_id');
            $table->index(['type', 'is_published']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->dropIndex(['type', 'is_published']);
            $table->dropColumn('type');
        });
    }
};
