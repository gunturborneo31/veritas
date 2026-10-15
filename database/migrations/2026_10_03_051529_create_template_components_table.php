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
        Schema::create('template_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('component_id')->constrained()->restrictOnDelete();
            $table->string('key');
            $table->string('title')->nullable();
            $table->unsignedSmallInteger('position');
            $table->boolean('is_required')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(['template_id', 'key']);
            $table->index(['template_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_components');
    }
};
