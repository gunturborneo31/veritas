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
            $table->timestamp('opens_at')->nullable()->after('content');
            $table->foreignId('referred_by_greeting_id')->nullable()->after('type')->constrained('greetings')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('greetings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referred_by_greeting_id');
            $table->dropColumn('opens_at');
        });
    }
};
