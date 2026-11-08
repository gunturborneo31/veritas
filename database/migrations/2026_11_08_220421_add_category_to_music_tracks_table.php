<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kategori musik latar (mis. Romantis, Ceria) untuk mengelompokkan pustaka di Studio.
     */
    public function up(): void
    {
        Schema::table('music_tracks', function (Blueprint $table) {
            $table->string('category', 60)->nullable()->after('mood')->index();
        });
    }

    public function down(): void
    {
        Schema::table('music_tracks', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropColumn('category');
        });
    }
};
