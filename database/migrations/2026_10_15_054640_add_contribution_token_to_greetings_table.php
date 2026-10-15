<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('greetings', function (Blueprint $table) {
            $table->string('contribution_token', 40)->nullable()->unique()->after('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('greetings', function (Blueprint $table) {
            $table->dropUnique(['contribution_token']);
            $table->dropColumn('contribution_token');
        });
    }
};
