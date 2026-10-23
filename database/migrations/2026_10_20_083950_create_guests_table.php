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
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('greeting_id')->constrained()->cascadeOnDelete();
            $table->string('code', 12);
            $table->string('name', 80);
            $table->string('phone', 20)->nullable();
            $table->string('group', 40)->nullable();
            $table->unsignedTinyInteger('quota')->default(2);
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamps();

            $table->unique(['greeting_id', 'code']);
            $table->index(['greeting_id', 'group']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
