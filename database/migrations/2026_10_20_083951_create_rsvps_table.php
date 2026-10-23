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
        Schema::create('rsvps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('greeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('attendance', 16);
            $table->unsignedTinyInteger('party_size')->default(0);
            $table->timestamps();

            $table->index(['greeting_id', 'attendance']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rsvps');
    }
};
