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
        Schema::create('open_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('greeting_id')->constrained()->cascadeOnDelete();
            $table->string('visitor_hash', 64);
            $table->string('user_agent')->nullable();
            $table->timestamp('opened_at');

            $table->index(['greeting_id', 'opened_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('open_events');
    }
};
