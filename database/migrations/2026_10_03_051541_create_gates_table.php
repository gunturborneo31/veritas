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
        Schema::create('gates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('greeting_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('type')->default('pin');
            $table->string('question')->nullable();
            $table->string('hint')->nullable();
            $table->string('answer_hash');
            $table->unsignedTinyInteger('answer_length');
            $table->unsignedTinyInteger('max_attempts')->default(5);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gates');
    }
};
