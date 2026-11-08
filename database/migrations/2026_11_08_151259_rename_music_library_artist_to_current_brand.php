<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Musik bawaan tercatat dengan artis "Pustaka KartuKita"; samakan dengan nama merek saat ini.
     */
    public function up(): void
    {
        DB::table('music_tracks')
            ->where('artist', 'Pustaka KartuKita')
            ->update(['artist' => 'Pustaka '.config('kartukita.brand')]);
    }

    public function down(): void
    {
        DB::table('music_tracks')
            ->where('artist', 'Pustaka '.config('kartukita.brand'))
            ->update(['artist' => 'Pustaka KartuKita']);
    }
};
